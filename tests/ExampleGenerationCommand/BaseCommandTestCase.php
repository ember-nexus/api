<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationCommand;

use Exception;
use PHPUnit\Framework\TestCase;

abstract class BaseCommandTestCase extends TestCase
{
    /**
     * Runs the command and returns its stdout. Stderr is captured as well: it contains e.g. progress bars, which would
     * otherwise end up in the test log without any context. It is only shown if the command fails.
     */
    public function runCommand(string $command): string
    {
        $output = [];
        $resultCode = 0;
        $stderrFile = \Safe\tempnam(sys_get_temp_dir(), 'ember-nexus-stderr-');
        try {
            \Safe\exec(sprintf('( %s ) 2>%s', $command, escapeshellarg($stderrFile)), $output, $resultCode);
            if (0 !== $resultCode) {
                throw new Exception(sprintf("Result code of command should be 0, got %d.\nCommand: %s\nStderr: %s", $resultCode, $command, substr(\Safe\file_get_contents($stderrFile), -2000)));
            }
        } finally {
            @unlink($stderrFile);
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
        array $ignoreLinesContainingString = [],
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
            echo sprintf(
                "\nAutomatically updated file %s.\n",
                $pathToDocumentationFile
            );
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
