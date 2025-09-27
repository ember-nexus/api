<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class CreatedElementPropertyChangeEventListener
{
    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if (!array_key_exists('created', $event->getChangedProperties())) {
            return;
        }
        throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate('created');
    }
}
