<?php

declare(strict_types=1);

namespace App\EventSystem\Exception\EventListener;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NoRouteFoundExceptionEventListener
{
    public function __construct(
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if (!($throwable instanceof NotFoundHttpException)) {
            return;
        }

        throw $this->client404NotFoundExceptionFactory->createFromTemplate($throwable);
    }
}
