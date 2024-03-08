<?php

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Service\EtagService;
use App\Type\Etag;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class IfMatchControllerEventListener
{
    public function __construct(
        private EtagService $etagService,
        private Client412PreconditionFailedExceptionFactory $client412PreconditionFailedExceptionFactory
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $attributes = $event->getAttributes(EndpointSupportsEtag::class);
        if (0 === count($attributes)) {
            return;
        }
        $currentRequestEtag = $this->etagService->getCurrentRequestEtag();
        if (null === $currentRequestEtag) {
            return;
        }
        if (!$event->getRequest()->headers->has('If-Match')) {
            return;
        }
        $rawEtags = $event->getRequest()->headers->get('If-Match');
        if (null == $rawEtags) {
            return;
        }
        $rawEtags = explode(',', $rawEtags);
        $etags = [];
        foreach ($rawEtags as $rawEtag) {
            $rawEtag = trim($rawEtag);
            if (str_starts_with($rawEtag, 'W/')) {
                // If a listed ETag has the W/ prefix indicating a weak entity tag, this comparison algorithm will never match it.
                continue;
            }
            $rawEtag = trim($rawEtag, '"');
            $etags[] = $rawEtag;
        }
        if (!in_array($currentRequestEtag->getEtag(), $etags)) {
            throw $this->client412PreconditionFailedExceptionFactory->createFromTemplate();
        }
    }
}
