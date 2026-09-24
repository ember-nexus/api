<?php

declare(strict_types=1);

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Service\EtagService;
use App\Type\Etag;
use App\Type\EtagType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class IfMatchControllerEventListener
{
    public function __construct(
        private EtagService $etagService,
        private Client412PreconditionFailedExceptionFactory $client412PreconditionFailedExceptionFactory,
    ) {
    }

    #[AsEventListener(priority: 128)]
    public function onKernelController(ControllerEvent $event): void
    {
        $attributes = $event->getAttributes(EndpointSupportsEtag::class);
        if (0 === count($attributes)) {
            return;
        }
        $currentRequestEtag = $this->etagService->getCurrentRequestEtag();
        if (!$event->getRequest()->headers->has('If-Match')) {
            return;
        }
        if (null === $currentRequestEtag) {
            // An element without file has no file representation, so nothing can match If-Match (RFC 9110). For other
            // types a missing ETag means 'not calculable', e.g. too large collections, which has to be ignored.
            $attribute = $attributes[0];
            /**
             * @var EndpointSupportsEtag $attribute
             */
            if (EtagType::FILE === $attribute->getEtagType()) {
                throw $this->client412PreconditionFailedExceptionFactory->createFromTemplate();
            }

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
            if ('*' === $rawEtag) {
                // matches every current representation, and there is one as the current ETag is not null
                return;
            }
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
