<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Cron;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class UpdateOwnershipTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testUpdateOwnershipHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:update-ownership --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/cron-update-ownership-help.html', $commandOutput);
    }

    public function testUpdateOwnership(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:update-ownership --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/cron-update-ownership.html',
            $commandOutput
        );
    }
}
