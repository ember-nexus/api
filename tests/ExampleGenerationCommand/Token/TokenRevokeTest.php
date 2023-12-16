<?php

namespace App\tests\ExampleGenerationCommand\Token;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class TokenRevokeTest extends BaseCommandTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testTokenRevokeHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console token:revoke --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/token-revoke-help.html', $commandOutput);
    }

    public function testTokenRevoke(): void
    {
        \Safe\exec('APP_ENV=prod php bin/console database:drop -f');
        \Safe\exec('APP_ENV=prod php bin/console backup:load reference-dataset');
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console token:revoke -f --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/token-revoke.html',
            $commandOutput,
            [
                'Found user with identifier',
                'Successfully revoked new token:',
                'localhost.dev'
            ]
        );
    }
}
