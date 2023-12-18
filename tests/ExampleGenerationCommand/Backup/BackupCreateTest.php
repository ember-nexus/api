<?php

namespace App\tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class BackupCreateTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testBackupCreateHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:create --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-create-help.html',
            $commandOutput,
            [
                'Name of the backup, defaults to the current timestamp.',
            ]
        );
    }

    public function testBackupCreate(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:create --ansi test | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-create.html',
            $commandOutput
        );
    }
}
