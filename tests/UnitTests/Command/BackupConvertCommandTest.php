<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\BackupConvertCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\StorageService;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use LogicException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Tester\CommandTester;

#[Small]
#[CoversClass(BackupConvertCommand::class)]
#[AllowMockObjectsWithoutExpectations]
class BackupConvertCommandTest extends TestCase
{
    private const string NODE_A = 'aee9e678-42fa-48cf-85e3-33638bebf0b1';
    private const string NODE_B = '6d1ccab4-559b-4086-92ac-370e30e40946';
    private const string RELATION = '81cc2ca9-bf66-4b37-a082-2490d1211e18';

    private string $storageRoot;
    private Filesystem $backupStorage;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/backup-convert-command-test-'.bin2hex(random_bytes(6));
        $this->backupStorage = new Filesystem(new LocalFilesystemAdapter($this->storageRoot));
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    protected function tearDown(): void
    {
        $this->backupStorage->deleteDirectory('');
        @rmdir($this->storageRoot);
    }

    private function runCommand(array $input): CommandTester
    {
        $storageService = new StorageService(
            $this->createMock(EmberNexusConfiguration::class),
            $this->createMock(Server500LogicErrorExceptionFactory::class)
        );
        $command = new BackupConvertCommand(
            $this->backupStorage,
            $storageService,
            new NullLogger(),
            $this->createMock(Server500LogicErrorExceptionFactory::class)
        );
        $tester = new CommandTester($command);
        $tester->execute($input);

        return $tester;
    }

    private function createLegacyBackup(): void
    {
        $this->backupStorage->write('legacy/summary.json', '{"nodeCount":2}');
        $this->backupStorage->write('legacy/node/human/a.json', "{\n  \"type\": \"Tag\",\n  \"id\": \"".self::NODE_A."\",\n  \"data\": {\n    \"created\": \"2020-01-01T00:00:00+00:00\"\n  }\n}\n");
        // nested folder structure as created by backup:create
        $this->backupStorage->write('legacy/node/6d/'.self::NODE_B.'.json', '{"type":"Tag","id":"'.self::NODE_B.'","data":{}}');
        $this->backupStorage->write('legacy/relation/r.json', '{"type":"HAS","id":"'.self::RELATION.'","start":"'.self::NODE_A.'","end":"'.self::NODE_B.'","data":{}}');
        $this->backupStorage->write('legacy/file/human/'.self::NODE_A.'.txt', 'content');
        $this->backupStorage->write('legacy/file/human/'.self::NODE_B, 'no extension');
    }

    public function testConvertsBackupWithoutTouchingSource(): void
    {
        $this->createLegacyBackup();
        $sourceBefore = $this->backupStorage->read('legacy/node/human/a.json');

        $tester = $this->runCommand(['source' => 'legacy']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame($sourceBefore, $this->backupStorage->read('legacy/node/human/a.json'));
        $this->assertTrue($this->backupStorage->fileExists('legacy/node/6d/'.self::NODE_B.'.json'));
        $this->assertTrue($this->backupStorage->fileExists('legacy/file/human/'.self::NODE_A.'.txt'));

        $csv = $this->backupStorage->read('legacy-converted/uuid-mapping.csv');
        $rows = array_map(static fn (string $line): array => explode(';', $line), array_slice(explode("\n", $csv), 1, 3));
        $mapping = array_column($rows, 1, 0);
        $this->assertSame('UUIDv4;UUIDsv2a7', explode("\n", $csv)[0]);
        $this->assertSame([self::NODE_B, self::RELATION, self::NODE_A], array_keys($mapping));
        $this->assertStringEndsWith("\n", $csv);

        $node = json_decode($this->backupStorage->read('legacy-converted/node/human/a.json'), true);
        $this->assertSame($mapping[self::NODE_A], $node['id']);
        $this->assertSame(1577836800000, Uuid::fromString($node['id'])->getTimestampMs());
        $this->assertStringContainsString("\n  \"type\": \"Tag\",\n", $this->backupStorage->read('legacy-converted/node/human/a.json'));
        $this->assertStringEndsWith("}\n", $this->backupStorage->read('legacy-converted/node/human/a.json'));

        $relation = json_decode($this->backupStorage->read('legacy-converted/relation/r.json'), true);
        $this->assertSame($mapping[self::RELATION], $relation['id']);
        $this->assertSame($mapping[self::NODE_A], $relation['start']);
        $this->assertSame($mapping[self::NODE_B], $relation['end']);

        // file names are renamed, human readable folders are kept
        $this->assertSame('content', $this->backupStorage->read('legacy-converted/file/human/'.$mapping[self::NODE_A].'.txt'));
        $this->assertSame('no extension', $this->backupStorage->read('legacy-converted/file/human/'.$mapping[self::NODE_B]));
        // nested folders are recalculated from the random part of the new id
        $nodeB = Uuid::fromString($mapping[self::NODE_B]);
        $this->assertTrue($this->backupStorage->fileExists(sprintf('legacy-converted/node/%s/%s.json', substr($nodeB->getRandomHex(), 0, 2), $mapping[self::NODE_B])));
        $this->assertTrue($this->backupStorage->fileExists('legacy-converted/summary.json'));
    }

    /**
     * @SuppressWarnings(PHPMD.ErrorControlOperator)
     */
    public function testCustomTargetAndMappingOutput(): void
    {
        $this->createLegacyBackup();
        $mappingFile = $this->storageRoot.'-mapping.csv';

        try {
            $this->runCommand(['source' => 'legacy', '--target' => 'custom', '--mapping-output' => $mappingFile]);

            $this->assertTrue($this->backupStorage->directoryExists('custom'));
            $this->assertFalse($this->backupStorage->fileExists('custom/uuid-mapping.csv'));
            $this->assertStringStartsWith("UUIDv4;UUIDsv2a7\n", \Safe\file_get_contents($mappingFile));
        } finally {
            @unlink($mappingFile);
        }
    }

    public function testExistingTargetIsRefused(): void
    {
        $this->createLegacyBackup();
        $this->backupStorage->write('legacy-converted/keep.txt', 'keep');

        $this->expectException(LogicException::class);
        try {
            $this->runCommand(['source' => 'legacy']);
        } finally {
            $this->assertSame('keep', $this->backupStorage->read('legacy-converted/keep.txt'));
        }
    }

    public function testUnknownSourceIsRefused(): void
    {
        $this->expectException(LogicException::class);
        $this->runCommand(['source' => 'missing']);
    }

    public function testConvertedBackupIsLeftUnchangedWhenConvertedAgain(): void
    {
        $this->createLegacyBackup();
        $this->runCommand(['source' => 'legacy']);
        $this->runCommand(['source' => 'legacy-converted', '--target' => 'again']);

        $this->assertSame(
            $this->backupStorage->read('legacy-converted/node/human/a.json'),
            $this->backupStorage->read('again/node/human/a.json')
        );
        $this->assertSame("UUIDv4;UUIDsv2a7\n", $this->backupStorage->read('again/uuid-mapping.csv'));
    }

    public function testCreatedRelationsBecomeCreatedByProperties(): void
    {
        $this->backupStorage->write('old/summary.json', "{\n  \"nodeCount\": 2,\n  \"relationCount\": 2,\n  \"fileCount\": 0,\n  \"hostname\": \"h\"\n}\n");
        // the creator is read after the created element, the order of the files must not matter
        $this->backupStorage->write('old/node/a-created.json', '{"type":"Data","id":"'.self::NODE_A.'","data":{"created":"2020-01-01T00:00:00+00:00"}}');
        $this->backupStorage->write('old/node/b-user.json', '{"type":"User","id":"'.self::NODE_B.'","data":{"created":"2019-01-01T00:00:00+00:00"}}');
        $this->backupStorage->write('old/relation/created.json', '{"type":"CREATED","id":"'.self::RELATION.'","start":"'.self::NODE_B.'","end":"'.self::NODE_A.'","data":{}}');
        $this->backupStorage->write('old/relation/owns.json', '{"type":"OWNS","id":"11111111-1111-4111-8111-111111111111","start":"'.self::NODE_B.'","end":"'.self::NODE_A.'","data":{}}');

        $tester = $this->runCommand(['source' => 'old']);
        $this->assertSame(0, $tester->getStatusCode());

        $user = json_decode($this->backupStorage->read('old-converted/node/b-user.json'), true);
        $created = json_decode($this->backupStorage->read('old-converted/node/a-created.json'), true);
        $this->assertSame($user['id'], $created['data']['createdBy']);
        $this->assertSame(1546300800000, Uuid::fromString($user['id'])->getTimestampMs(), 'the creator keeps the time of its creation');
        $this->assertArrayNotHasKey('createdBy', $user['data']);

        $this->assertFalse($this->backupStorage->fileExists('old-converted/relation/created.json'));
        $this->assertTrue($this->backupStorage->fileExists('old-converted/relation/owns.json'));

        $summary = json_decode($this->backupStorage->read('old-converted/summary.json'), true);
        $this->assertSame(2, $summary['nodeCount']);
        $this->assertSame(1, $summary['relationCount']);
        $this->assertSame(['nodeTypes' => ['Data', 'User'], 'relationTypes' => ['OWNS']], $summary['graphStructure']);
        $this->assertSame(['nodeCount', 'relationCount', 'fileCount', 'graphStructure', 'hostname'], array_keys($summary));
    }

    /**
     * @return array<string, array{string, bool}>
     */
    public static function versionProvider(): array
    {
        return [
            'older release' => ['0.1.9', true],
            'v prefix' => ['v0.1.0', true],
            'development build' => ['dev', true],
            'first new version' => ['0.2.0', false],
            'pre-release of the new version' => ['0.2.0-rc1', false],
            'newer release' => ['1.4.2', false],
        ];
    }

    #[DataProvider('versionProvider')]
    public function testOnlyBackupsOfOlderVersionsAreConverted(string $version, bool $converted): void
    {
        $this->createLegacyBackup();
        $this->backupStorage->write('legacy/summary.json', json_encode(['nodeCount' => 2, 'version' => $version]));

        if (!$converted) {
            try {
                $this->runCommand(['source' => 'legacy']);
                $this->fail('Expected the conversion to be refused.');
            } catch (LogicException $exception) {
                $this->assertStringContainsString('--force', $exception->getMessage());
                $this->assertStringContainsString($version, $exception->getMessage());
            }
            $this->assertFalse($this->backupStorage->directoryExists('legacy-converted'), 'nothing is written when refused');

            return;
        }
        $this->assertSame(0, $this->runCommand(['source' => 'legacy'])->getStatusCode());
        $this->assertTrue($this->backupStorage->directoryExists('legacy-converted'));
    }

    public function testForceConvertsBackupsOfNewerVersions(): void
    {
        $this->createLegacyBackup();
        $this->backupStorage->write('legacy/summary.json', json_encode(['nodeCount' => 2, 'version' => '0.2.0']));

        $tester = $this->runCommand(['source' => 'legacy', '--force' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Converting it anyway', $tester->getDisplay());
    }

    public function testBackupWithoutVersionIsConvertedWithWarning(): void
    {
        $this->createLegacyBackup();
        $this->backupStorage->write('legacy/summary.json', '{"nodeCount":2}');

        $tester = $this->runCommand(['source' => 'legacy']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString("no 'version'", $tester->getDisplay());
    }
}
