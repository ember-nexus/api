<?php

namespace App\EventListener;

use App\Exception\SecurityException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ExceptionEventListener
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $status = 500;
        if ($exception instanceof SecurityException) {
            $status = 403;
        }

        $instance = null;

        if ($event->getResponse()?->headers?->has('X-Debug-Token')) {
            $this->urlGenerator->generate(
                '_profiler',
                [
                    'token' => $event->getResponse()?->headers?->get('X-Debug-Token'),
                ],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $data = [
            'type' => get_class($exception),
            'title' => $exception->getMessage(),
            'status' => $status,
            'instance' => $instance,
        ];

        if (!$instance) {
            unset($data['instance']);
        }

        $event->setResponse(new JsonResponse(
            $data,
            $status,
            [
                'Content-Type' => 'application/problem+json',
            ]
        ));
        $event->stopPropagation();
    }
}
