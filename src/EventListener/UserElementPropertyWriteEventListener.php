<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;
use App\Type\NodeElement;

class UserElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        $element = $event->getElement();
        if (!($element instanceof NodeElement)) {
            return;
        }
        if ('User' !== $element->getLabel()) {
            return;
        }
        if ($event->hasProperty('type')) {
            throw new ClientBadRequestException(detail: 'Manually setting the type property is forbidden.');
        }
    }
}
