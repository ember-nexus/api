<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReset\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;

class UserElementPropertyResetEventListener
{
    public function onElementPropertyResetEvent(ElementPropertyResetEvent $event): void
    {
        $element = $event->getElement();
        if (!($element instanceof NodeElementInterface)) {
            return;
        }
        if ('User' !== $element->getLabel()) {
            return;
        }
        $event->addPropertyNameToBeKept('_passwordHash');
    }
}
