<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Cron;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class ReindexFilesTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testReindexFilesHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:reindex-files --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/cron-reindex-files-help.html', $commandOutput);
    }

    public function testReindexFiles(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:reindex-files --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/cron-reindex-files.html',
            $commandOutput
        );
    }
}
