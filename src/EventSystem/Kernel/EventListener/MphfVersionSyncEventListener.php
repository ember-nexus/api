<?php

declare(strict_types=1);

namespace App\EventSystem\Kernel\EventListener;

use App\Service\Mphf\MphfVersionSynchronizer;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Makes sure that every API request and every CLI command works with the MPHF build which Redis names as current.
 */
class MphfVersionSyncEventListener
{
    /**
     * Commands which never resolve element types, or which manage the MPHF themselves.
     */
    private const array SKIPPED_COMMAND_PREFIXES = ['cache:', 'lint:', 'debug:', 'assets:', 'mphf:', 'healthcheck', 'help', 'list', 'about', 'completion', '_complete'];

    public function __construct(
        // lazy: the synchronizer needs Redis, which commands on the skip list (like the cache:clear during the image build)
        // must not require just because this listener exists
        #[Autowire(lazy: true)]
        private MphfVersionSynchronizer $mphfVersionSynchronizer,
    ) {
    }

    #[AsEventListener(KernelEvents::REQUEST, priority: 4096)]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->mphfVersionSynchronizer->synchronize();
    }

    /**
     * Downloads run after the response was sent, so no request has to wait for them.
     */
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[AsEventListener(KernelEvents::TERMINATE)]
    public function onKernelTerminate(TerminateEvent $event): void
    {
        if ($this->mphfVersionSynchronizer->hasPendingDownload()) {
            $this->mphfVersionSynchronizer->downloadPending();
        }
    }

    #[AsEventListener(ConsoleEvents::COMMAND, priority: 4096)]
    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $name = $event->getCommand()?->getName() ?? '';
        foreach (self::SKIPPED_COMMAND_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return;
            }
        }
        $this->mphfVersionSynchronizer->synchronize(true);
    }
}
