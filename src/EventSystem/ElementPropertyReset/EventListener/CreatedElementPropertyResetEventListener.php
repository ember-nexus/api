<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReset\EventListener;

use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;

class CreatedElementPropertyResetEventListener
{
    public function onElementPropertyResetEvent(ElementPropertyResetEvent $event): void
    {
        $event->addPropertyNameToBeKept('created');
    }
}
