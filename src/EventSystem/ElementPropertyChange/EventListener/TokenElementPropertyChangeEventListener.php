<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class TokenElementPropertyChangeEventListener
{
    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('Token' !== $event->getLabelOrType()) {
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
