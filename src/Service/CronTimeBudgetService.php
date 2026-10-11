<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Shared time budget for the cron commands: an unattended invocation (the scheduler, or a plain, non-tty `docker
 * exec`) should stop starting new work after `MAX_WORK_TIME_IN_S` seconds, so it does not run into (or past) the
 * next scheduled tick. A developer running a cron command by hand in a real terminal is never time-limited.
 */
class CronTimeBudgetService
{
    private const int DEFAULT_MAX_WORK_TIME_IN_SECONDS = 180;

    public function __construct(
        private TtyDetectorService $ttyDetectorService,
    ) {
    }

    public function isInteractive(): bool
    {
        return $this->ttyDetectorService->isInteractive();
    }

    /**
     * Unix timestamp after which no new unit of work should be started, or null when running interactively.
     */
    public function getDeadline(): ?int
    {
        if ($this->isInteractive()) {
            return null;
        }

        return time() + $this->getMaxWorkTimeInSeconds();
    }

    private function getMaxWorkTimeInSeconds(): int
    {
        $raw = getenv('MAX_WORK_TIME_IN_S');
        if (false === $raw || !is_numeric($raw)) {
            return self::DEFAULT_MAX_WORK_TIME_IN_SECONDS;
        }

        return (int) $raw;
    }
}
