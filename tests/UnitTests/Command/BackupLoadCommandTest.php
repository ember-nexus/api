<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\BackupLoadCommand;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Service\AppStateService;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileSizeLimitService;
use App\Service\RawToElementService;
use App\Service\S3Service;
use App\Type\NodeElement;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

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

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/backup-load-command-test-'.bin2hex(random_bytes(6));
        $this->backupStorage = new Filesystem(new LocalFilesystemAdapter($this->storageRoot));
        $this->backupStorage->createDirectory(self::BACKUP_NAME.'/file');
        $this->backupStorage->write(self::BACKUP_NAME.'/summary.json', '{"nodeCount":0,"relationCount":0,"fileCount":0}');
        $this->elements = [];
        $this->uploadedIds = [];
        $this->failingIds = [];
    }

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
        $id = Uuid::uuid4();
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

    /**
     * @param array<string, mixed> $input
     */
    private function runCommand(array $input = [], int $maxFileSizeInBytes = 1024): CommandTester
    {
        $test = $this;

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->flush()->willReturn($elementManager->reveal());
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

        $uploadFileOperationFactory = $this->prophesize(UploadFileOperationFactory::class);
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

        $command = new BackupLoadCommand(
            $elementManager->reveal(),
            $cypherEntityManager->reveal(),
            $redis,
            $this->backupStorage,
            $this->prophesize(RawToElementService::class)->reveal(),
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
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $tester = new CommandTester($command);
        $statusCode = $tester->execute(['name' => self::BACKUP_NAME, ...$input]);
        $this->assertSame(Command::SUCCESS, $statusCode);

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
}
