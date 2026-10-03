<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use App\Contract\EtagCapableResponseInterface;
use App\Service\EtagService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class EtagResponseEventListener
{
    public function __construct(
        private EtagService $etagService,
    ) {
    }

    #[AsEventListener]
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        if (!($response instanceof EtagCapableResponseInterface)) {
            return;
        }
        $etag = $this->etagService->getCurrentRequestEtag();
        if (null === $etag) {
            return;
        }
        $response->setEtagFromEtagInstance($etag);
    }
}
