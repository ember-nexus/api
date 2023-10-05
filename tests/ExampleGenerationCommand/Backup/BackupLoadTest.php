<?php

namespace App\tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class BackupLoadTest extends BaseCommandTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testBackupListHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:load --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-load-help.html',
            $commandOutput,
            [
            ]
        );
    }

    public function testBackupList(): void
    {
        $this->runCommand('php bin/console database:drop -f');
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:load reference-dataset --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-load.html',
            $commandOutput
        );
    }
}
