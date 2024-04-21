<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationCommand;

use Exception;
use PHPUnit\Framework\TestCase;

abstract class BaseCommandTestCase extends TestCase
{
    public function runCommand(string $command): string
    {
        $output = [];
        $resultCode = 0;
        \Safe\exec($command, $output, $resultCode);
        if (0 !== $resultCode) {
            throw new Exception(sprintf('Result code of command should be 0, got %d.', $resultCode));
        }

        return implode("\n", $output);
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
            echo(sprintf(
                "\nAutomatically updated file %s.\n",
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
