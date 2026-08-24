<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Cron;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class DeleteExpiredUploadsTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testDeleteExpiredUploadsHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:delete-expired-uploads --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/cron-delete-expired-uploads-help.html', $commandOutput);
    }

    public function testDeleteExpiredUploads(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:delete-expired-uploads --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/cron-delete-expired-uploads.html',
            $commandOutput
        );
    }
}
