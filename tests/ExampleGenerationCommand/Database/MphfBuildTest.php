<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand\Database;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class MphfBuildTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testMphfBuildHelp(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console mphf:build --ansi --help | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'tests/Recordings/commands/assets/mphf-build-help.html', $commandOutput);
    }

    public function testMphfBuild(): void
    {
        $commandOutput = $this->runCommand(sprintf(
            'APP_ENV=prod VERSION=%s php bin/console mphf:build --ansi | aha -s --black --css "./cli-style.css"',
            $this->getCurrentVersion()
        ));
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(
            self::PATH_TO_ROOT,
            'tests/Recordings/commands/assets/mphf-build.html',
            $commandOutput,
            ['Version ', 'Exported ', 'Building the index']
        );
    }
}
