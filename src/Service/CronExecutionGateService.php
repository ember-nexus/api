<?php

declare(strict_types=1);

namespace App\Service;

use LogicException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Shared skip decision for the cron commands: `DISABLE_CRON` only suppresses execution while the process is not
 * attached to a real terminal. This lets an unattended invocation (the scheduler, or a plain, non-tty `docker
 * exec`) be suppressed, while a developer running a cron command by hand in a real terminal always executes it,
 * regardless of `DISABLE_CRON`.
 */
class CronExecutionGateService
{
    public function __construct(
        private ParameterBagInterface $bag,
        private TtyDetectorService $ttyDetectorService,
    ) {
    }

    public function shouldSkipExecution(): bool
    {
        $isCronDisabled = $this->bag->get('isCronDisabled');
        if (!is_bool($isCronDisabled)) {
            throw new LogicException(sprintf('Expected "isCronDisabled" to be of type boolean, got %s.', get_debug_type($isCronDisabled)));
        }
        if (!$isCronDisabled) {
            return false;
        }

        return !$this->ttyDetectorService->isInteractive();
    }
}
