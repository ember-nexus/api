<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;
use App\Type\RelationElement;

class EndElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->getElement() instanceof RelationElement && $event->hasProperty('end')) {
            throw new ClientBadRequestException(detail: 'Manually setting the end property is forbidden.');
        }
    }
}
