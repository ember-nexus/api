<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;

class IdElementPropertyChangeEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if (!array_key_exists('id', $event->getChangedProperties())) {
            return;
        }
        throw new \Exception("Setting the property 'id' is forbidden.");
    }
}
