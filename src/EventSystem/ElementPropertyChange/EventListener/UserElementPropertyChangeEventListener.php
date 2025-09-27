<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UserElementPropertyChangeEventListener
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('User' !== $event->getLabelOrType()) {
            return;
        }
        if (array_key_exists('_passwordHash', $event->getChangedProperties())) {
            throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate('_passwordHash');
        }
        if (array_key_exists($this->emberNexusConfiguration->getRegisterUniqueIdentifier(), $event->getChangedProperties())) {
            throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate($this->emberNexusConfiguration->getRegisterUniqueIdentifier());
        }
    }
}
