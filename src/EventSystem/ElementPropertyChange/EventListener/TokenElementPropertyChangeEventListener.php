<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;

class TokenElementPropertyChangeEventListener
{
    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory
    ) {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('Token' !== $event->getType()) {
            return;
        }
        if (array_key_exists('token', $event->getChangedProperties())) {
            throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate('token');
        }
        if (array_key_exists('_tokenHash', $event->getChangedProperties())) {
            throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate('_tokenHash');
        }
    }
}
