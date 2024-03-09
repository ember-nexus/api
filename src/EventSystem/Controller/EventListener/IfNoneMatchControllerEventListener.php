<?php

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Response\NotModifiedResponse;
use App\Service\EtagService;
use App\Type\Etag;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class IfNoneMatchControllerEventListener
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
        if (!$event->getRequest()->headers->has('If-None-Match')) {
            return;
        }
        $rawEtags = $event->getRequest()->headers->get('If-None-Match');
        if (null == $rawEtags) {
            return;
        }
        $rawEtags = explode(',', $rawEtags);
        $etags = [];
        foreach ($rawEtags as $rawEtag) {
            $rawEtag = trim($rawEtag);
            // The comparison with the stored ETag uses the weak comparison algorithm, meaning two files are
            // considered identical if the content is equivalent — they don't have to be identical byte by byte.
            // For example, two pages that differ by their creation date in the footer would still be considered
            // identical.
            if (str_starts_with($rawEtag, 'W/')) {
                $rawEtag = substr($rawEtag, 2);
            }
            $rawEtag = trim($rawEtag, '"');
            $etags[] = $rawEtag;
        }
        if (in_array($currentRequestEtag->getEtag(), $etags)) {
            if (in_array($event->getRequest()->getMethod(), ['GET', 'HEAD'])) {
                $event->setController(function () {
                    return new NotModifiedResponse();
                });

                return;
            }
            throw $this->client412PreconditionFailedExceptionFactory->createFromTemplate();
        }
    }
}
