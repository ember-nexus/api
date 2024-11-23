<?php

declare(strict_types=1);

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\Service\EtagService;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class EtagControllerEventListener
{
    public function __construct(
        private EtagService $etagService,
    ) {
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $attributes = $event->getAttributes(EndpointSupportsEtag::class);
        if (0 === count($attributes)) {
            return;
        }
        $endpointSupportsEtagAttribute = $attributes[0];
        /**
         * @var EndpointSupportsEtag $endpointSupportsEtagAttribute
         */
        $this->etagService->setCurrentRequestEtagFromRequestAndEtagType($event->getRequest(), $endpointSupportsEtagAttribute->getEtagType());
    }
}
