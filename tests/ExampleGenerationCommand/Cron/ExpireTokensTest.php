<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Cron;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class ExpireTokensTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testExpireTokensHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:expire-tokens --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'tests/Recordings/commands/assets/cron-expire-tokens-help.html', $commandOutput);
    }

    public function testExpireTokens(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console cron:expire-tokens --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/cron-expire-tokens.html',
            $commandOutput
        );
    }
}
