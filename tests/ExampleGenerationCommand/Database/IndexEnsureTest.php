<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Database;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class IndexEnsureTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testIndexEnsureHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console index:ensure --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'tests/Recordings/commands/assets/index-ensure-help.html', $commandOutput);
    }

    public function testIndexEnsure(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console index:ensure --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/index-ensure.html',
            $commandOutput,
            []
        );
    }
}
