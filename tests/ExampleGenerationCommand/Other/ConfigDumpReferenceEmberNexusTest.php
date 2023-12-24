<?php

namespace App\tests\ExampleGenerationCommand\Other;

use App\Tests\ExampleGenerationCommand\BaseCommandTestCase;

class ConfigDumpReferenceEmberNexusTest extends BaseCommandTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testUserCreateHelp(): void
    {
        $commandOutput = $this->runCommand(
            'APP_ENV=dev php bin/console config:dump-reference ember_nexus'
        );
        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/example/default-parameters.yaml', $commandOutput);
    }
}
