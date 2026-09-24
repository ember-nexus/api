<?php

declare(strict_types=1);

namespace App\EventSystem\Exception\EventListener;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client405MethodNotAllowedExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NoRouteFoundExceptionEventListener
{
    public function __construct(
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Client405MethodNotAllowedExceptionFactory $client405MethodNotAllowedExceptionFactory,
    ) {
    }

    #[AsEventListener(priority: 2048)]
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        if ($throwable instanceof MethodNotAllowedHttpException) {
            throw $this->client405MethodNotAllowedExceptionFactory->createFromTemplate();
        }
        if (!($throwable instanceof NotFoundHttpException)) {
            return;
        }

        throw $this->client404NotFoundExceptionFactory->createFromTemplate($throwable);
    }
}
