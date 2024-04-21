<?php

declare(strict_types=1);

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

        $commandOutput = $this->replaceStartingSpaces($commandOutput);
        $commandOutput = \Safe\preg_replace('/: +/', ': ', $commandOutput);

        $this->assertCommandOutputIsIdenticalToDocumentedCommandOutput(self::PATH_TO_ROOT, 'docs/example/default-parameters.yaml', $commandOutput);
    }

    public function replaceStartingSpaces(string $inputString): string
    {
        return preg_replace_callback(
            '/^( +)/m',
            fn ($matches) => str_repeat(' ', (int) floor(strlen($matches[0]) / 2)),
            $inputString
        );
    }
}
