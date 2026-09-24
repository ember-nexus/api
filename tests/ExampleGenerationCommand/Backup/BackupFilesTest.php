<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Checks the file handling of `backup:create` and `backup:load` against the real database and S3 storage. The
 * output is not compared against documentation snapshots, so no files in `docs/` are needed.
 *
 * The details (skip decisions, size limit, error isolation) are covered by unit tests.
 */
class BackupFilesTest extends BaseCommandTestCase
{
    private const string BACKUP_DIRECTORY = __DIR__.'/../../../var/backup/';
    private const string ROUND_TRIP_BACKUP = 'files-round-trip-test';
    private const string TAMPERED_BACKUP = 'files-tampered-test';

    protected function tearDown(): void
    {
        foreach ([self::ROUND_TRIP_BACKUP, self::TAMPERED_BACKUP] as $name) {
            $this->runCommand(sprintf('rm -rf %s', escapeshellarg(self::BACKUP_DIRECTORY.$name)));
        }
        // leave the database in the state other tests expect
        $this->runCommand('php bin/console database:drop -f');
        $this->runCommand('php bin/console backup:load reference-dataset');
    }

    private function getFileCountFromCreateOutput(string $output): int
    {
        $this->assertSame(1, preg_match('/Successfully backed up (\d+) files\./', $output, $matches), $output);

        return (int) $matches[1];
    }

    public function testBackupCreateAndLoadRoundTripIncludesFiles(): void
    {
        $this->runCommand('php bin/console database:drop -f');
        $this->runCommand('php bin/console backup:load reference-dataset');

        $createOutput = $this->runCommand(sprintf('php bin/console backup:create --no-ansi %s', self::ROUND_TRIP_BACKUP));
        $fileCount = $this->getFileCountFromCreateOutput($createOutput);
        $this->assertGreaterThan(0, $fileCount);

        $this->runCommand('php bin/console database:drop -f');
        $loadOutput = $this->runCommand(sprintf('php bin/console backup:load --no-ansi %s', self::ROUND_TRIP_BACKUP));

        $this->assertStringContainsString(sprintf('Loaded %d files.', $fileCount), $loadOutput);
        $this->assertStringNotContainsString('could not be loaded', $loadOutput);
    }

    public function testBackupLoadSkipsFileWithMismatchingHashUnlessSkipVerify(): void
    {
        $this->runCommand('php bin/console database:drop -f');
        $this->runCommand('php bin/console backup:load reference-dataset');
        $createOutput = $this->runCommand(sprintf('php bin/console backup:create --no-ansi %s', self::TAMPERED_BACKUP));
        $fileCount = $this->getFileCountFromCreateOutput($createOutput);
        $this->assertGreaterThan(0, $fileCount);
        $this->tamperWithOneBackupFile(self::TAMPERED_BACKUP);

        $this->runCommand('php bin/console database:drop -f');
        $verifiedOutput = $this->runCommand(sprintf('php bin/console backup:load --no-ansi %s', self::TAMPERED_BACKUP));
        $this->assertStringContainsString(sprintf('Loaded %d files, 1 could not be loaded', $fileCount - 1), $verifiedOutput);

        $this->runCommand('php bin/console database:drop -f');
        $unverifiedOutput = $this->runCommand(sprintf('php bin/console backup:load --no-ansi --skip-verify %s', self::TAMPERED_BACKUP));
        $this->assertStringContainsString(sprintf('Loaded %d files.', $fileCount), $unverifiedOutput);
    }

    private function tamperWithOneBackupFile(string $backupName): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::BACKUP_DIRECTORY.$backupName.'/file', FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                \Safe\file_put_contents($file->getPathname(), 'tampered', FILE_APPEND);

                return;
            }
        }
        $this->fail('Backup contains no file which could be tampered with.');
    }
}
