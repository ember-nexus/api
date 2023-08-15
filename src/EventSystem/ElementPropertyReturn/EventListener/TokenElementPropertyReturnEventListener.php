<?php

namespace App\EventSystem\ElementPropertyReturn\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;

class TokenElementPropertyReturnEventListener
{
    public function __construct()
    {
    }

    public function onElementPropertyReturnEvent(ElementPropertyReturnEvent $event): void
    {
        $element = $event->getElement();
        if (!($element instanceof NodeElementInterface)) {
            return;
        }
        if ('Token' !== $element->getLabel()) {
            return;
        }
        $event->addBlockedProperty('token');
        $event->addBlockedProperty('_tokenHash');
        $event->addBlockedProperty('hash');
    }
}
