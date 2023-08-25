<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;

class UserElementPropertyChangeEventListener
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('User' !== $event->getType()) {
            return;
        }
        if (array_key_exists('_passwordHash', $event->getChangedProperties())) {
            throw new Exception("Setting the property '_passwordHash' is forbidden.");
        }
        if (array_key_exists($this->emberNexusConfiguration->getRegisterUniqueIdentifier(), $event->getChangedProperties())) {
            throw new Exception(sprintf("Setting the property '%s' is forbidden.", $this->emberNexusConfiguration->getRegisterUniqueIdentifier()));
        }
    }
}
