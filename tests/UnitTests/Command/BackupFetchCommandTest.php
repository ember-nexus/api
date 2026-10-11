<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\BackupFetchCommand;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Covers the `source` argument validation of `backup:fetch`; actual downloads are covered by the feature tests.
 */
#[Small]
#[CoversClass(BackupFetchCommand::class)]
class BackupFetchCommandTest extends TestCase
{
    private string $storageRoot;
    private Filesystem $backupStorage;

    protected function setUp(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/backup-fetch-command-test-'.bin2hex(random_bytes(6));
        $this->backupStorage = new Filesystem(new LocalFilesystemAdapter($this->storageRoot));
    }

    protected function tearDown(): void
    {
        $this->backupStorage->deleteDirectory('');
        @rmdir($this->storageRoot);
    }

    private function runCommand(string $source): CommandTester
    {
        $command = new BackupFetchCommand($this->backupStorage);
        $tester = new CommandTester($command);
        $tester->execute(['name' => 'test-backup', 'source' => $source]);

        return $tester;
    }

    /**
     * @return array<string, string[]>
     */
    public static function invalidSourceSchemeProvider(): array
    {
        return [
            'local filesystem path' => ['/tmp/backup.zip'],
            'ftp scheme' => ['ftp://example.com/backup.zip'],
            'file scheme' => ['file:///etc/passwd'],
            'phar scheme' => ['phar:///tmp/backup.zip'],
        ];
    }

    #[DataProvider('invalidSourceSchemeProvider')]
    public function testNonHttpSourceIsRejected(string $source): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf("Source must be a HTTP(S) URL, got '%s'.", $source));

        $this->runCommand($source);
    }
}
