<?php

namespace App\Tests\ExampleGenerationCommand;

use PHPUnit\Framework\TestCase;

abstract class BaseCommandTestCase extends TestCase
{
    public function runCommand(string $command): string
    {
        return \Safe\shell_exec($command);
    }

    /**
     * @param string[] $ignoreLinesContainingString
     */
    public function assertCommandOutputIsIdenticalToDocumentedCommandOutput(
        string $pathToProjectRoot,
        string $pathToDocumentationFile,
        string $commandOutput,
        array $ignoreLinesContainingString = []
    ): void {
        $documentationCommandOutput = file_get_contents($pathToProjectRoot.$pathToDocumentationFile);
        $filteredDocumentationCommandOutput = [];
        foreach (explode("\n", $documentationCommandOutput) as $line) {
            foreach ($ignoreLinesContainingString as $ignoredLine) {
                if (str_contains($line, $ignoredLine)) {
                    continue 2;
                }
            }
            $filteredDocumentationCommandOutput[] = $line;
        }

        $filteredCommandOutput = [];
        foreach (explode("\n", $commandOutput) as $line) {
            foreach ($ignoreLinesContainingString as $ignoredLine) {
                if (str_contains($line, $ignoredLine)) {
                    continue 2;
                }
            }
            $filteredCommandOutput[] = $line;
        }

        if (array_key_exists('FIX_COMMAND_OUTPUT', $_ENV)) {
            $this->addWarning(sprintf(
                'Automatically updated file %s.',
                $pathToDocumentationFile
            ));
            \Safe\file_put_contents(
                $pathToProjectRoot.$pathToDocumentationFile,
                $commandOutput
            );
            $this->assertTrue(true);

            return;
        }

        $this->assertSame(
            $filteredDocumentationCommandOutput,
            $filteredCommandOutput,
            sprintf(
                'File %s must be updated, fix it by adding env flag FIX_COMMAND_OUTPUT=true.',
                $pathToProjectRoot.$pathToDocumentationFile
            )
        );
    }

    public function getCurrentVersion(): string
    {
        $composerJson = json_decode(\Safe\file_get_contents(__DIR__.'/../../composer.json'), true);

        return $composerJson['version'];
    }
}
