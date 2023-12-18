<?php

namespace App\Tests\ExampleGenerationCommand\User;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class UserCreateTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testUserCreateHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console user:create --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/user-create-help.html', $commandOutput);
    }

    public function testUserCreate(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console user:create --ansi command-test@localhost.dev 1234 | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/user-create.html',
            $commandOutput,
            [
                'Created user with email',
            ]
        );
    }
}
