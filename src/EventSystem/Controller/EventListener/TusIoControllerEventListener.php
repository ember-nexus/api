<?php

declare(strict_types=1);

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointImplementsTusIo;
use App\Factory\Exception\Client412TusResumablePreconditionFailedExceptionFactory;
use App\Service\TusIoService;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class TusIoControllerEventListener
{
    public function __construct(
        private TusIoService $tusIoService,
        private Client412TusResumablePreconditionFailedExceptionFactory $client412TusResumablePreconditionFailedExceptionFactory
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $attributes = $event->getAttributes(EndpointImplementsTusIo::class);
        if (0 === count($attributes)) {
            return;
        }

        $this->tusIoService->setIsTusIoEndpoint(true);

        if (!$event->getRequest()->headers->has('Tus-Resumable')) {
            throw $this->client412TusResumablePreconditionFailedExceptionFactory->createFromTemplate();
        }

        if ('1.0.0' !== $event->getRequest()->headers->get('Tus-Resumable')) {
            throw $this->client412TusResumablePreconditionFailedExceptionFactory->createFromTemplate();
        }
    }
}
