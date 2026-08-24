<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;
use ZipArchive;

/**
 * `backup:fetch` supports two archive layouts: `node`/`relation`/`file`/`summary.json` directly at the root of
 * the ZIP (e.g. what `git archive` produces without a `--prefix`), or wrapped inside a single top level folder
 * (e.g. what GitHub's release archives produce). This verifies both layouts extract nested files to the correct
 * path, since a nested file's directory structure was previously being lost/corrupted during extraction.
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

    private function runBackupFetchCommand(string $backupName, string $zipPath): void
    {
        $command = sprintf(
            'php bin/console backup:fetch %s %s --force',
            escapeshellarg($backupName),
            escapeshellarg($zipPath)
        );
        $resultCode = 0;
        \Safe\exec($command, result_code: $resultCode);
        $this->assertSame(0, $resultCode, sprintf('Command should succeed: %s', $command));
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
     * file nested one directory deep, optionally wrapped inside a single top level folder.
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
