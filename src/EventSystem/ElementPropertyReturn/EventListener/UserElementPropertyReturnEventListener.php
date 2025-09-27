<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReturn\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UserElementPropertyReturnEventListener
{
    #[AsEventListener]
    public function onElementPropertyReturnEvent(ElementPropertyReturnEvent $event): void
    {
        $element = $event->getElement();
        if (!($element instanceof NodeElementInterface)) {
            return;
        }
        if ('User' !== $element->getLabel()) {
            return;
        }
        $event->addBlockedProperty('_passwordHash');
    }
}
