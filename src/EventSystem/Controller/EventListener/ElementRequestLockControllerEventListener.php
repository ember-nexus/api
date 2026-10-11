<?php

declare(strict_types=1);

namespace App\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Service\ElementRequestLockService;
use App\Type\EtagType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Implicit per-request lock on the element a `PATCH`/`PUT`/`DELETE /{id}` request targets, held for the whole
 * read-check-write sequence. Must run before the `Etag`/`If-Match` checks on the same `kernel.controller` event, so
 * its priority has to stay higher than theirs; released on `kernel.response` even if the controller threw.
 */
class ElementRequestLockControllerEventListener
{
    private const array LOCKED_METHODS = ['PATCH', 'PUT', 'DELETE'];

    private const string REQUEST_ATTRIBUTE_ELEMENT_ID = '_elementRequestLockElementId';
    private const string REQUEST_ATTRIBUTE_TOKEN = '_elementRequestLockToken';

    public function __construct(
        private ElementRequestLockService $elementRequestLockService,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    #[AsEventListener(priority: 320)]
    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if (!in_array($request->getMethod(), self::LOCKED_METHODS, true)) {
            return;
        }
        $attributes = $event->getAttributes(EndpointSupportsEtag::class);
        if (0 === count($attributes)) {
            return;
        }
        /**
         * @var EndpointSupportsEtag $attribute
         */
        $attribute = $attributes[0];
        if (EtagType::ELEMENT !== $attribute->getEtagType()) {
            return;
        }
        $rawElementId = $request->attributes->get('id');
        if (!is_string($rawElementId)) {
            return;
        }
        $elementId = UuidV4::fromString($rawElementId);

        $token = $this->elementRequestLockService->acquire($elementId);
        if (null === $token) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Another request is currently modifying the element with id '%s', please retry once it has finished.", $elementId->toString()));
        }

        $request->attributes->set(self::REQUEST_ATTRIBUTE_ELEMENT_ID, $elementId);
        $request->attributes->set(self::REQUEST_ATTRIBUTE_TOKEN, $token);
    }

    #[AsEventListener]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        $elementId = $request->attributes->get(self::REQUEST_ATTRIBUTE_ELEMENT_ID);
        $token = $request->attributes->get(self::REQUEST_ATTRIBUTE_TOKEN);
        if (null === $elementId || null === $token) {
            return;
        }
        $request->attributes->remove(self::REQUEST_ATTRIBUTE_ELEMENT_ID);
        $request->attributes->remove(self::REQUEST_ATTRIBUTE_TOKEN);
        $this->elementRequestLockService->release($elementId, $token);
    }
}
