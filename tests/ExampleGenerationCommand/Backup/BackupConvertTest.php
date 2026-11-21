<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class BackupConvertTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testBackupConvertHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:convert --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/backup-convert-help.html',
            $commandOutput,
            [
            ]
        );
    }

    public function testBackupConvert(): void
    {
        // test files run in alphabetical order, i.e. before BackupFetchTest has fetched the dataset
        $fetchedDataset = !is_dir(self::PATH_TO_ROOT.'var/backup/reference-dataset');
        $this->runCommand(sprintf(
            'test -d var/backup/reference-dataset || APP_ENV=prod php bin/console backup:fetch reference-dataset https://github.com/ember-nexus/reference-dataset/archive/refs/tags/%s.zip',
            $this->getReferenceDatasetVersion()
        ));
        $this->runCommand('rm -rf var/backup/reference-dataset-converted');
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:convert --ansi reference-dataset --target reference-dataset-converted | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->runCommand('rm -rf var/backup/reference-dataset-converted');
        if ($fetchedDataset) {
            // BackupFetchTest fetches the dataset itself and fails if it already exists
            $this->runCommand('rm -rf var/backup/reference-dataset');
        }
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/backup-convert.html',
            $commandOutput,
            [
            ]
        );
    }
}
