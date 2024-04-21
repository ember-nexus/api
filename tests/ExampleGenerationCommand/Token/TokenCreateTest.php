<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationCommand\Token;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class TokenCreateTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testTokenCreateHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console token:create --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/token-create-help.html', $commandOutput);
    }

    public function testTokenCreate(): void
    {
        $this->runCommand(
            'APP_ENV=prod php bin/console user:create token-create-test@localhost.dev 1234'
        );
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console token:create --ansi token-create-test@localhost.dev 1234 | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/token-create.html',
            $commandOutput,
            [
                'Found user with identifier',
                'Successfully created new token:',
            ]
        );
    }
}
