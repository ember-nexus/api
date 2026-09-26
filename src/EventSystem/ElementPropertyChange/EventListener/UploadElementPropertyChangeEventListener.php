<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * All properties of `Upload` elements are internal state of the upload endpoints, e.g. the offset or the hash state
 * of the integrity check, and are only written by the server itself, which does not dispatch this event.
 */
class UploadElementPropertyChangeEventListener
{
    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('Upload' !== $event->getLabelOrType()) {
            return;
        }
        foreach (array_keys($event->getChangedProperties()) as $propertyName) {
            throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate((string) $propertyName);
        }
    }
}
