<?php

declare(strict_types=1);

namespace App\Tests\UnitTests;

use Beste\Psr\Log\TestLogger;

trait AssertLoggerTrait
{
    abstract public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void;

    abstract public static function fail(string $message = ''): never;

    public function assertLogHappened(TestLogger $logger, string $level, string $message, array|false $data = []): ?array
    {
        $filteredMessages = $logger->records->filteredByMessageContaining($message)->all();
        if (0 === count($filteredMessages)) {
            $allLogMessages = [];
            foreach ($logger->records->all() as $record) {
                $allLogMessages[] = sprintf('  %s', $record->message);
            }
            $this->fail(sprintf(
                "Unable to find log with content \"%s\".\nPossible log messages:\n%s",
                $message,
                join("\n", $allLogMessages)
            ));
        }
        if (count($filteredMessages) > 1) {
            $this->fail(sprintf('Found multiple logs with content "%s"', $message));
        }
        $logMessage = $filteredMessages[0];
        $this->assertSame($level, $logMessage->level);
        if (false === $data) {
            return $logMessage->context->data;
        } else {
            $this->assertSame($data, $logMessage->context->data);

            return null;
        }
    }
}
