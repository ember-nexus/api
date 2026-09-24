<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\BackupCreateCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\ElementToRawService;
use App\Service\FileService;
use App\Service\S3Service;
use App\Service\StringService;
use App\Type\NodeElement;
use App\Type\S3\FileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Covers the file handling of `backup:create` (step 3). The test databases contain no nodes or relations, only
 * elements with files.
 */
#[Small]
#[CoversClass(BackupCreateCommand::class)]
class BackupCreateCommandTest extends TestCase
{
    use ProphecyTrait;

    private const string BACKUP_NAME = 'test-backup';

    private string $storageRoot;
    private Filesystem $backupStorage;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/backup-create-command-test-'.bin2hex(random_bytes(6));
        $this->backupStorage = new Filesystem(new LocalFilesystemAdapter($this->storageRoot));
    }

    protected function tearDown(): void
    {
        $this->backupStorage->deleteDirectory('');
        @rmdir($this->storageRoot);
    }

    /**
     * @param array<string, string> $fileContents      element id => content of its file in S3; ids missing from the
     *                                                 element manager can be added to $missingElementIds
     * @param string[]              $missingElementIds
     */
    private function runCommand(array $fileContents, array $input = [], array $missingElementIds = []): CommandTester
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->will(function (array $args) use ($missingElementIds) {
            if (in_array($args[0]->toString(), $missingElementIds, true)) {
                return null;
            }
            $element = new NodeElement();
            $element->setId($args[0]);

            return $element;
        });

        $summaryReference = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use (&$summaryReference, $fileContents) {
            $text = $args[0]->getText();
            if (str_contains($text, 'count(')) {
                return new SummarizedResult($summaryReference, [new CypherMap(['count' => 0])]);
            }
            if (str_contains($text, 'element.id')) {
                return new SummarizedResult($summaryReference, array_map(
                    fn (string $id) => new CypherMap(['element.id' => $id]),
                    array_keys($fileContents)
                ));
            }

            return new SummarizedResult($summaryReference, []);
        });
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::any())->willReturn('bin');

        $fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $fileOperationFactory->createFileOperationFromElement(Argument::any())->will(function (array $args) {
            return new FileOperation('storage', $args[0]->getId()->toString());
        });

        $s3Service = $this->prophesize(S3Service::class);
        $s3Service->getFileAsResource(Argument::any())->will(function (array $args) use ($fileContents) {
            $resource = fopen('php://memory', 'r+');
            fwrite($resource, $fileContents[$args[0]->getKey()]);
            rewind($resource);

            return $resource;
        });

        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('version')->willReturn('0.0.0-test');

        $command = new BackupCreateCommand(
            $elementManager->reveal(),
            $cypherEntityManager->reveal(),
            $this->backupStorage,
            $this->prophesize(ElementToRawService::class)->reveal(),
            $elementService->reveal(),
            $bag->reveal(),
            new FileService(
                $this->prophesize(EmberNexusConfiguration::class)->reveal(),
                new StringService($this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()),
                $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
            ),
            $s3Service->reveal(),
            $fileOperationFactory->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $tester = new CommandTester($command);
        $this->assertSame(Command::SUCCESS, $tester->execute(['name' => self::BACKUP_NAME, ...$input]));

        return $tester;
    }

    /**
     * @return array<string, mixed>
     */
    private function readSummary(): array
    {
        return json_decode($this->backupStorage->read(self::BACKUP_NAME.'/summary.json'), true);
    }

    public function testFilesAreWrittenToBackupAndCountedInSummary(): void
    {
        $idA = Uuid::uuid4()->toString();
        $idB = Uuid::uuid4()->toString();

        $tester = $this->runCommand([$idA => 'content a', $idB => '']);

        $this->assertSame('content a', $this->backupStorage->read(sprintf('%s/file/%s.bin', self::BACKUP_NAME, $idA)));
        $this->assertSame('', $this->backupStorage->read(sprintf('%s/file/%s.bin', self::BACKUP_NAME, $idB)));
        $this->assertSame(2, $this->readSummary()['fileCount']);
        $this->assertStringContainsString('Successfully backed up 2 files.', $tester->getDisplay());
    }

    public function testNoFilesOptionSkipsFileExport(): void
    {
        $id = Uuid::uuid4()->toString();

        $tester = $this->runCommand([$id => 'content'], ['--no-files' => true]);

        $this->assertSame([], $this->backupStorage->listContents(self::BACKUP_NAME.'/file', true)->toArray());
        $this->assertSame(0, $this->readSummary()['fileCount']);
        $this->assertStringContainsString('File backup skipped.', $tester->getDisplay());
    }

    public function testBackupWithoutFilesReportsSkippedFileBackup(): void
    {
        $tester = $this->runCommand([]);

        $this->assertSame(0, $this->readSummary()['fileCount']);
        $this->assertStringContainsString('Found 0 files.', $tester->getDisplay());
        $this->assertStringContainsString('File backup skipped.', $tester->getDisplay());
    }

    public function testFileOfVanishedElementIsSkipped(): void
    {
        $present = Uuid::uuid4()->toString();
        $vanished = Uuid::uuid4()->toString();

        $this->runCommand([$present => 'a', $vanished => 'b'], [], [$vanished]);

        $this->assertTrue($this->backupStorage->fileExists(sprintf('%s/file/%s.bin', self::BACKUP_NAME, $present)));
        $this->assertFalse($this->backupStorage->fileExists(sprintf('%s/file/%s.bin', self::BACKUP_NAME, $vanished)));
    }
}
