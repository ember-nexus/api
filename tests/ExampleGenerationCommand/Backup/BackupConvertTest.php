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
        $this->runCommand('rm -rf var/backup/reference-dataset-converted');
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:convert --ansi reference-dataset --target reference-dataset-converted | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->runCommand('rm -rf var/backup/reference-dataset-converted');
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/backup-convert.html',
            $commandOutput,
            [
            ]
        );
    }
}
