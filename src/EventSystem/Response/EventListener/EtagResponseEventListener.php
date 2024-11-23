<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use App\Response\CollectionResponse;
use App\Response\ElementResponse;
use App\Response\NotModifiedResponse;
use App\Service\EtagService;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class EtagResponseEventListener
{
    public function __construct(
        private EtagService $etagService,
    ) {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!(
            $response instanceof CollectionResponse
            || $response instanceof ElementResponse
            || $response instanceof NotModifiedResponse
        )) {
            return;
        }
        $etag = $this->etagService->getCurrentRequestEtag();
        if (null === $etag) {
            return;
        }
        $response->setEtagFromEtagInstance($etag);
    }
}
