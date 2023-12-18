<?php

namespace App\tests\ExampleGenerationCommand\Backup;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class BackupFetchTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testBackupFetchHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:fetch --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-fetch-help.html',
            $commandOutput,
            [
            ]
        );
    }

    public function testBackupFetch(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console backup:fetch --ansi reference-dataset https://github.com/ember-nexus/reference-dataset/archive/refs/tags/0.0.19.zip | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/backup-fetch.html',
            $commandOutput,
            [
                ' [',
            ]
        );
    }
}
