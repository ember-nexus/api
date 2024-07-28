<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use App\Service\TusIoService;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class TusIoResponseEventListener
{
    public function __construct(
        private TusIoService $tusIoService
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->tusIoService->isTusIoEndpoint()) {
            return;
        }
        header('Tus-Resumable: 1.0.0');
        header('Tus-Extension: creation,creation-defer-length,creation-with-upload,expiration,checksum,checksum-trailer,termination,concatenation');
    }
}
