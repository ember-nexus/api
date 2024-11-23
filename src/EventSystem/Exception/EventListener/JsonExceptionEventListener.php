<?php

declare(strict_types=1);

namespace App\EventSystem\Exception\EventListener;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use Safe\Exceptions\JsonException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class JsonExceptionEventListener
{
    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!($throwable instanceof JsonException)) {
            return;
        }

        throw $this->client400BadContentExceptionFactory->createFromJsonException($throwable);
    }
}
