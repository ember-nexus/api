<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Verifies that `backup:fetch` extracts nested files to the correct path, both for archives with the backup
 * content at the ZIP root and for archives wrapped in a single top level folder (e.g. GitHub release archives).
 */
#[Group('command')]
class BackupFetchFolderStructureTest extends BaseRequestTestCase
{
    public function testFetchFromArchiveWithBackupContentAtRoot(): void
    {
        $zipPath = $this->buildFixtureZip(null);
        $backupName = 'backup-fetch-folder-structure-root-test';

        $this->runBackupFetchCommand($backupName, $zipPath);
        $this->assertNestedFileWasExtractedCorrectly($backupName);

        unlink($zipPath);
    }

    public function testFetchFromArchiveWithBackupContentWrappedInASingleFolder(): void
    {
        $zipPath = $this->buildFixtureZip('wrapper-folder');
        $backupName = 'backup-fetch-folder-structure-wrapped-test';

        $this->runBackupFetchCommand($backupName, $zipPath);
        $this->assertNestedFileWasExtractedCorrectly($backupName);

        unlink($zipPath);
    }

    /**
     * `backup:fetch` only accepts HTTP(S) sources, so the fixture ZIP is served over a local built-in PHP
     * webserver rather than being passed as a filesystem path.
     */
    private function runBackupFetchCommand(string $backupName, string $zipPath): void
    {
        $port = random_int(20000, 60000);
        $server = new Process(['php', '-S', sprintf('127.0.0.1:%d', $port), '-t', dirname($zipPath)]);
        $server->start();
        usleep(300000);

        try {
            $command = sprintf(
                'php bin/console backup:fetch %s %s --force',
                escapeshellarg($backupName),
                escapeshellarg(sprintf('http://127.0.0.1:%d/%s', $port, basename($zipPath)))
            );
            $resultCode = 0;
            \Safe\exec($command, result_code: $resultCode);
            $this->assertSame(0, $resultCode, sprintf('Command should succeed: %s', $command));
        } finally {
            $server->stop();
        }
    }

    private function assertNestedFileWasExtractedCorrectly(string $backupName): void
    {
        foreach (['node', 'relation', 'file'] as $topLevelFolder) {
            $extractedFilePath = sprintf('var/backup/%s/%s/nested/folder/data.json', $backupName, $topLevelFolder);
            $this->assertFileExists($extractedFilePath);
            $this->assertSame(sprintf('{"nested":"%s"}', $topLevelFolder), file_get_contents($extractedFilePath));
        }
    }

    /**
     * Builds a minimal fixture ZIP containing a `summary.json`, and `node`/`relation`/`file` folders, each with a
     * file nested two directories deep, optionally wrapped inside a single top level folder.
     */
    private function buildFixtureZip(?string $wrapperFolder): string
    {
        $zipPath = sprintf('%s/backup-fetch-folder-structure-%s.zip', sys_get_temp_dir(), uniqid());
        $prefix = null === $wrapperFolder ? '' : $wrapperFolder.'/';

        $zip = new ZipArchive();
        $zip->open($zipPath, ZipArchive::CREATE);
        if (null !== $wrapperFolder) {
            $zip->addEmptyDir($wrapperFolder);
        }
        $zip->addFromString($prefix.'summary.json', '{"nodeCount":1,"relationCount":1,"fileCount":0}');
        foreach (['node', 'relation', 'file'] as $topLevelFolder) {
            $zip->addEmptyDir($prefix.$topLevelFolder);
            $zip->addEmptyDir($prefix.$topLevelFolder.'/nested');
            $zip->addEmptyDir($prefix.$topLevelFolder.'/nested/folder');
            $zip->addFromString(
                $prefix.$topLevelFolder.'/nested/folder/data.json',
                sprintf('{"nested":"%s"}', $topLevelFolder)
            );
        }
        $zip->close();

        return $zipPath;
    }
}
