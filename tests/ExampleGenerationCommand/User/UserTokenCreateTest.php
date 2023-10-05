<?php

namespace App\tests\ExampleGenerationCommand\User;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class UserTokenCreateTest extends BaseCommandTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testUserTokenCreateHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console user:token:create --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/user-token-create-help.html', $commandOutput);
    }

    public function testUserTokenCreate(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console user:token:create --ansi command-test@localhost.dev 1234 | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/user-token-create.html',
            $commandOutput,
            [
                'Found user with identifier',
                'Successfully created new token:',
            ]
        );
    }
}
