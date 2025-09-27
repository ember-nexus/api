<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReset\EventListener;

use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class IdElementPropertyResetEventListener
{
    #[AsEventListener]
    public function onElementPropertyResetEvent(ElementPropertyResetEvent $event): void
    {
        $event->addPropertyNameToBeKept('id');
    }
}
