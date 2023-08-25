<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use Exception;

class TokenElementPropertyChangeEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('Token' !== $event->getType()) {
            return;
        }
        if (array_key_exists('token', $event->getChangedProperties())) {
            throw new Exception("Setting the property 'token' is forbidden.");
        }
        if (array_key_exists('_tokenHash', $event->getChangedProperties())) {
            throw new Exception("Setting the property '_tokenHash' is forbidden.");
        }
    }
}
