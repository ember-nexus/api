<?php

namespace App\EventSystem\ElementPropertyReturn\EventListener;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;

class IdElementPropertyReturnEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyReturnEvent(ElementPropertyReturnEvent $event): void
    {
        $event->addBlockedProperty('id');
    }
}
