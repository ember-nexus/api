<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Debug\TraceableEventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Stopwatch\Stopwatch;

class DeactivatableTraceableEventDispatcher extends TraceableEventDispatcher
{
    private bool $isDeactivated = false;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        Stopwatch $stopwatch,
        ?LoggerInterface $logger = null,
        ?RequestStack $requestStack = null
    ) {
        $this->eventDispatcher = $dispatcher;
        parent::__construct($dispatcher, $stopwatch, $logger, $requestStack);
    }

    public function isDeactivated(): bool
    {
        $this->logger?->notice('Deactivated TraceableEventDispatcher');

        return $this->isDeactivated;
    }

    public function deactivate(): self
    {
        $this->logger?->notice('Activated TraceableEventDispatcher');
        $this->isDeactivated = true;

        return $this;
    }

    public function activate(): self
    {
        $this->isDeactivated = false;

        return $this;
    }

    public function dispatch(object $event, ?string $eventName = null): object
    {
        if ($this->isDeactivated) {
            return $this->eventDispatcher->dispatch($event, $eventName);
        }

        return parent::dispatch($event, $eventName);
    }
}
