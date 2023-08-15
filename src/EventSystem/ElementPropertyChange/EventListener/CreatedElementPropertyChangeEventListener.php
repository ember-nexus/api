<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;

class CreatedElementPropertyChangeEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if (!array_key_exists('created', $event->getChangedProperties())) {
            return;
        }
        throw new \Exception("Setting the property 'created' is forbidden.");
    }
}
