<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReturn\EventListener;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;

class IdElementPropertyReturnEventListener
{
    public function onElementPropertyReturnEvent(ElementPropertyReturnEvent $event): void
    {
        $event->addBlockedProperty('id');
    }
}
