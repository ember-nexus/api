<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationCommand\Database;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class DatabaseDropTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testUserCreateHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console database:drop --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/commands/assets/database-drop-help.html', $commandOutput);
    }

    public function testUserCreate(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console database:drop -f --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'docs/commands/assets/database-drop.html',
            $commandOutput,
            [
            ]
        );
    }
}
