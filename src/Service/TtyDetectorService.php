<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Detects whether the current process has a real terminal (tty) attached to STDIN. Deliberately not based on
 * Symfony Console's `InputInterface::isInteractive()`, which only reflects the `--no-interaction` flag and
 * Symfony's own defaulting, not actual tty presence, and would not distinguish an unattended invocation (e.g.
 * `docker exec api php bin/console cron` without `-t`) from a developer's real interactive terminal session.
 */
class TtyDetectorService
{
    public function isInteractive(): bool
    {
        if (!defined('STDIN') || !function_exists('stream_isatty')) {
            // no STDIN or no way to check it, e.g. some SAPIs or restricted environments: assume non-interactive
            return false;
        }

        // false means that there is no terminal, which is a valid result and not an error as the Safe variant expects
        return stream_isatty(STDIN); // @phpstan-ignore theCodingMachineSafe.function
    }
}
