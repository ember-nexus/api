<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\BackupConvertCommand;
use App\Command\BackupLoadCommand;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Service\AppStateService;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileSizeLimitService;
use App\Service\GraphStructureService;
use App\Service\Index\IndexEnsurer;
use App\Service\Index\IndexEnsureResult;
use App\Service\MaintenanceLockService;
use App\Service\Mphf\MphfBuildResult;
use App\Service\Mphf\MphfBuildService;
use App\Service\RawToElementService;
use App\Service\S3Service;
use App\Service\StorageService;
use App\Type\MaintenanceLock;
use App\Type\MaintenanceLockType;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\S3\UploadFileOperation;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use InvalidArgumentException;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Throwable;

/**
 * Covers the file handling of `backup:load` (step 3): hash verification, `--skip-verify`, size limit and error
 * isolation. The test backups contain no nodes or relations, so the other steps are no-ops.
 */
#[Small]
#[CoversClass(BackupLoadCommand::class)]
class BackupLoadCommandTest extends TestCase
{
    use ProphecyTrait;

    private const string BACKUP_NAME = 'test-backup';
    private const string CONTENT = 'hello backup';

    private string $storageRoot;
    private Filesystem $backupStorage;

    /** @var array<string, NodeElement> */
    private array $elements = [];

    /** @var string[] ids of elements which were uploaded to S3 */
    private array $uploadedIds = [];

    /** @var string[] ids of elements for which the upload throws */
    private array $failingIds = [];

    /** @var string[] ids of relations which were created */
    private array $createdRelationIds = [];

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/backup-load-command-test-'.bin2hex(random_bytes(6));
        $this->backupStorage = new Filesystem(new LocalFilesystemAdapter($this->storageRoot));
        $this->backupStorage->createDirectory(self::BACKUP_NAME.'/file');
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0}');
        $this->graphStructureService = $this->prophesize(GraphStructureService::class);
        $this->elements = [];
        $this->uploadedIds = [];
        $this->failingIds = [];
        $this->createdRelationIds = [];
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    protected function tearDown(): void
    {
        $this->backupStorage->deleteDirectory('');
        @rmdir($this->storageRoot);
    }

    /**
     * @param array<string, mixed>|null $fileProperty
     */
    private function addBackupFile(string $content, ?array $fileProperty): string
    {
        $id = Uuid::generate();
        $this->backupStorage->write(sprintf('%s/file/%s.bin', self::BACKUP_NAME, $id->toString()), $content);
        $element = new NodeElement();
        $element->setId($id);
        if (null !== $fileProperty) {
            $element->addProperty('file', $fileProperty);
        }
        $this->elements[$id->toString()] = $element;

        return $id->toString();
    }

    /**
     * @return array<string, mixed>
     */
    private function validFileProperty(string $content = self::CONTENT): array
    {
        return ['hash' => ['sha256' => hash('sha256', $content)], 'extension' => 'bin'];
    }

    private bool $mphfBuildFails = false;
    private ObjectProphecy $locks;
    private ObjectProphecy $indexEnsurer;
    private ObjectProphecy $mphfBuildService;
    private ObjectProphecy $graphStructureService;

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = [], int $maxFileSizeInBytes = 1024, bool $lockTaken = false, int $expectedStatusCode = Command::SUCCESS): CommandTester
    {
        $test = $this;

        $locks = $this->prophesize(MaintenanceLockService::class);
        $locks->claim(MaintenanceLockType::DATABASE)->willReturn($lockTaken ? null : new MaintenanceLock(MaintenanceLockType::DATABASE, 'a'));
        $locks->claim(MaintenanceLockType::DATABASE_INDICES)->willReturn(new MaintenanceLock(MaintenanceLockType::DATABASE_INDICES, 'b'));
        $locks->claim(MaintenanceLockType::MPHF)->willReturn(new MaintenanceLock(MaintenanceLockType::MPHF, 'c'));
        $locks->release(Argument::type(MaintenanceLock::class))->willReturn(true);
        $indexEnsurer = $this->prophesize(IndexEnsurer::class);
        $indexEnsurer->ensure(Argument::cetera())->willReturn(new IndexEnsureResult());
        $mphfBuildService = $this->prophesize(MphfBuildService::class);
        if ($this->mphfBuildFails) {
            $mphfBuildService->build(Argument::cetera())->willThrow(new Exception('boom'));
        } else {
            $mphfBuildService->build(Argument::cetera())->willReturn(new MphfBuildResult(false, null, '20260101-0000.mphf', 3));
        }
        $this->locks = $locks;
        $this->indexEnsurer = $indexEnsurer;
        $this->mphfBuildService = $mphfBuildService;

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->flush()->willReturn($elementManager->reveal());
        $elementManager->create(Argument::any())->will(function (array $args) use ($test, $elementManager) {
            $test->createdRelationIds[] = $args[0]->getId()->toString();

            return $elementManager->reveal();
        });
        $elementManager->getElement(Argument::any())->will(
            fn (array $args) => $test->elements[$args[0]->toString()] ?? null
        );

        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use (&$summaryReference) {
            $isCount = str_contains($args[0]->getText(), 'count(');

            return new SummarizedResult($summaryReference, $isCount ? [new CypherMap(['count' => 0])] : []);
        });
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->flush()->willReturn($elasticEntityManager->reveal());

        $redis = new class extends Client {
            public function __call($commandID, $arguments)
            {
                return null;
            }
        };

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileMaxFileSizeInBytes()->willReturn($maxFileSizeInBytes);

        $uploadFileOperationFactory = $this->prophesize(S3OperationFactory::class);
        $uploadFileOperationFactory->createUploadFileOperationFromElementAndResource(
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->will(fn (array $args) => new UploadFileOperation(
            'upload',
            'upload-key',
            'storage',
            null,
            $args[0]->getId()->toString(),
            $args[1],
            $args[2],
            'text/plain'
        ));

        $s3Service = $this->prophesize(S3Service::class);
        $s3Service->uploadFile(Argument::any())->will(function (array $args) use ($test) {
            $id = $args[0]->getStorageKey();
            if (in_array($id, $test->failingIds, true)) {
                throw new Exception('S3 is down');
            }
            $test->uploadedIds[] = $id;

            return 0;
        });

        $rawToElementService = $this->prophesize(RawToElementService::class);
        $rawToElementService->rawToElement(Argument::any(), true)->will(function (array $args) {
            if (!is_string($args[0]['id'] ?? null)) {
                throw new InvalidArgumentException('Relation has no valid id.');
            }

            return (new RelationElement())->setId(Uuid::fromString($args[0]['id']));
        });

        $command = new BackupLoadCommand(
            $elementManager->reveal(),
            $cypherEntityManager->reveal(),
            $redis,
            $this->backupStorage,
            $rawToElementService->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            new AppStateService(),
            $elasticEntityManager->reveal(),
            $s3Service->reveal(),
            $uploadFileOperationFactory->reveal(),
            new FileSizeLimitService(
                $emberNexusConfiguration->reveal(),
                $this->prophesize(Client400BadContentExceptionFactory::class)->reveal()
            ),
            new FileHashService(),
            new NullLogger(),
            $locks->reveal(),
            $indexEnsurer->reveal(),
            $mphfBuildService->reveal(),
            $this->graphStructureService->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );

        $tester = new CommandTester($command);
        $statusCode = $tester->execute(['name' => self::BACKUP_NAME, ...$input]);
        $this->assertSame($expectedStatusCode, $statusCode);

        return $tester;
    }

    public function testFileWithMatchingHashIsLoaded(): void
    {
        $id = $this->addBackupFile(self::CONTENT, $this->validFileProperty());

        $tester = $this->runCommand();

        $this->assertSame([$id], $this->uploadedIds);
        $this->assertStringContainsString('Loaded 1 files.', $tester->getDisplay());
    }

    public function testFileWithMismatchingHashIsSkipped(): void
    {
        $this->addBackupFile('tampered content', $this->validFileProperty());

        $tester = $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString("does not match 'file.hash.sha256'", $tester->getDisplay());
        $this->assertStringContainsString('Loaded 0 files, 1 could not be loaded', $tester->getDisplay());
    }

    public function testFileWithoutHashIsSkipped(): void
    {
        $this->addBackupFile(self::CONTENT, ['extension' => 'bin']);

        $tester = $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString("no verifiable 'file.hash' property", $tester->getDisplay());
    }

    public function testFileOfElementWithoutFilePropertyIsSkipped(): void
    {
        $this->addBackupFile(self::CONTENT, null);

        $tester = $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString("no verifiable 'file.hash' property", $tester->getDisplay());
    }

    public function testFileWithOnlyUnsupportedHashAlgorithmIsSkipped(): void
    {
        $this->addBackupFile(self::CONTENT, ['hash' => ['blake3' => 'abc']]);

        $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
    }

    public function testSkipVerifyLoadsFileWithMismatchingHash(): void
    {
        $id = $this->addBackupFile('tampered content', $this->validFileProperty());

        $tester = $this->runCommand(['--skip-verify' => true]);

        $this->assertSame([$id], $this->uploadedIds);
        $this->assertStringContainsString('--skip-verify', $tester->getDisplay());
    }

    public function testSkipVerifyLoadsFileWithoutHash(): void
    {
        $id = $this->addBackupFile(self::CONTENT, null);

        $this->runCommand(['--skip-verify' => true]);

        $this->assertSame([$id], $this->uploadedIds);
    }

    public function testOversizedFileIsSkippedEvenWithSkipVerify(): void
    {
        $this->addBackupFile(self::CONTENT, $this->validFileProperty());

        $tester = $this->runCommand(['--skip-verify' => true], 5);

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString('File is 12 bytes, which exceeds', $tester->getDisplay());
    }

    public function testFileExactlyAtSizeLimitIsLoaded(): void
    {
        $id = $this->addBackupFile(self::CONTENT, $this->validFileProperty());

        $this->runCommand([], strlen(self::CONTENT));

        $this->assertSame([$id], $this->uploadedIds);
    }

    public function testFileWithoutCorrespondingElementIsSkipped(): void
    {
        $id = $this->addBackupFile(self::CONTENT, $this->validFileProperty());
        unset($this->elements[$id]);

        $tester = $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString('without corresponding element', $tester->getDisplay());
    }

    public function testFileWithNonUuidNameIsIgnored(): void
    {
        $this->backupStorage->write(self::BACKUP_NAME.'/file/not-a-uuid.bin', self::CONTENT);

        $tester = $this->runCommand();

        $this->assertSame([], $this->uploadedIds);
        $this->assertStringContainsString('Loaded 0 files.', $tester->getDisplay());
    }

    public function testFailingFileDoesNotAbortRestoreOfOtherFiles(): void
    {
        $failing = $this->addBackupFile(self::CONTENT, $this->validFileProperty());
        $tampered = $this->addBackupFile('tampered content', $this->validFileProperty());
        $good = $this->addBackupFile(self::CONTENT, $this->validFileProperty());
        $this->failingIds = [$failing];

        $tester = $this->runCommand();

        $this->assertSame([$good], $this->uploadedIds);
        $this->assertNotContains($tampered, $this->uploadedIds);
        $this->assertStringContainsString('S3 is down', $tester->getDisplay());
        $this->assertStringContainsString('Loaded 1 files, 2 could not be loaded', $tester->getDisplay());
    }

    public function testBrokenRelationFilesDoNotAbortRestoreOfOtherRelations(): void
    {
        $good1 = Uuid::generate()->toString();
        $good2 = Uuid::generate()->toString();
        $this->backupStorage->write(self::BACKUP_NAME.'/relation/good-1.json', sprintf('{"type":"RELATION","id":"%s"}', $good1));
        $this->backupStorage->write(self::BACKUP_NAME.'/relation/invalid-json.json', 'this is not json');
        $this->backupStorage->write(self::BACKUP_NAME.'/relation/without-id.json', '{"type":"RELATION"}');
        $this->backupStorage->write(self::BACKUP_NAME.'/relation/good-2.json', sprintf('{"type":"RELATION","id":"%s"}', $good2));

        $tester = $this->runCommand();

        $this->assertEqualsCanonicalizing([$good1, $good2], $this->createdRelationIds);
        // the console wraps long error messages
        $display = (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
        $this->assertStringContainsString('relation/invalid-json.json; skipping relation', $display);
        $this->assertStringContainsString('relation/without-id.json; skipping relation: InvalidArgumentException: Relation has no valid id.', $display);
        $this->assertStringContainsString('Loaded 2 relations, skipped 2 broken relation files', $display);
    }

    public function testEnsuresIndicesAfterNodesAndRelationsForBackupsWithoutGraphStructureAndBuildsMphf(): void
    {
        $tester = $this->runCommand();
        $this->graphStructureService->setStructure(Argument::cetera())->shouldNotHaveBeenCalled();

        $this->indexEnsurer->ensure(true, false, Argument::any())->shouldHaveBeenCalledOnce();
        $this->indexEnsurer->ensure(false, true, Argument::any())->shouldHaveBeenCalledOnce();
        $this->mphfBuildService->build(Argument::cetera())->shouldHaveBeenCalledOnce();
        $this->assertStringContainsString('20260101-0000.mphf', $tester->getDisplay());
    }

    public function testGraphStructureOfSummaryCreatesAllIndicesBeforeAnythingIsLoaded(): void
    {
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0,"graphStructure":{"nodeTypes":["Data","User"],"relationTypes":["OWNS"]}}');
        $this->graphStructureService->setStructure(['Data', 'User'], ['OWNS'])->shouldBeCalledOnce();
        $this->graphStructureService->refresh()->shouldBeCalledOnce();

        $this->runCommand();

        // once, for nodes and relations together, with the structure of the summary instead of the empty database
        $this->indexEnsurer->ensure(true, true, Argument::any(), false, false)->shouldHaveBeenCalledOnce();
        $this->indexEnsurer->ensure(true, false, Argument::cetera())->shouldNotHaveBeenCalled();
        $this->indexEnsurer->ensure(false, true, Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testInvalidGraphStructureOfSummaryFailsTheLoad(): void
    {
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"graphStructure":{"nodeTypes":["Data",3],"relationTypes":[]}}');

        $this->expectException(Throwable::class);
        $this->runCommand();
    }

    public function testBackupsOfOlderVersionsAreRefusedAndPointToConvert(): void
    {
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0,"version":"0.1.4"}');

        try {
            $this->runCommand();
            $this->fail('Expected the load to be refused.');
        } catch (LogicException $exception) {
            $this->assertStringContainsString('backup:convert '.self::BACKUP_NAME, $exception->getMessage());
        }
    }

    public function testConvertedBackupOfAnOlderVersionIsLoaded(): void
    {
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0,"version":"0.1.36"}');
        $convert = new BackupConvertCommand(
            $this->backupStorage,
            new StorageService(
                $this->createStub(EmberNexusConfiguration::class),
                $this->createStub(Server500LogicErrorExceptionFactory::class)
            ),
            new NullLogger(),
            $this->createStub(Server500LogicErrorExceptionFactory::class)
        );
        $this->assertSame(0, (new CommandTester($convert))->execute(['source' => self::BACKUP_NAME, '--target' => 'converted']));

        $this->assertSame(0, $this->runCommand(['name' => 'converted'])->getStatusCode());
    }

    public function testBackupsOfNewDevelopmentAndUnknownVersionsAreLoaded(): void
    {
        foreach (['0.2.0', '0.2.0-rc1', '1.0.0', 'dev'] as $version) {
            $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0,"version":"'.$version.'"}');
            $this->assertSame(0, $this->runCommand()->getStatusCode(), $version);
        }
    }

    public function testGraphStructureIsRefreshedAfterTheLoad(): void
    {
        $this->graphStructureService->refresh()->shouldBeCalledOnce();

        $this->runCommand();
    }

    public function testSkipMphfBuildOptionDoesNotBuild(): void
    {
        $this->runCommand(['--skip-mphf-build' => true]);

        $this->mphfBuildService->build(Argument::cetera())->shouldNotHaveBeenCalled();
    }

    public function testFailingMphfBuildOnlyWarns(): void
    {
        $this->mphfBuildFails = true;
        $tester = $this->runCommand();

        $this->assertStringContainsString('boom', $tester->getDisplay());
    }

    public function testTakenLockFailsWithoutLoading(): void
    {
        $tester = $this->runCommand(lockTaken: true, expectedStatusCode: Command::FAILURE);

        $this->assertStringContainsString('lock:maintenance:database', $tester->getDisplay());
        $this->indexEnsurer->ensure(Argument::cetera())->shouldNotHaveBeenCalled();
        $this->mphfBuildService->build(Argument::cetera())->shouldNotHaveBeenCalled();
    }
}
