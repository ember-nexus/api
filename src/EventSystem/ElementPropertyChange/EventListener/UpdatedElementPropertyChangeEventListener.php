<?php

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;

class UpdatedElementPropertyChangeEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if (!array_key_exists('updated', $event->getChangedProperties())) {
            return;
        }
        throw new \Exception("Setting the property 'updated' is forbidden.");
    }
}
