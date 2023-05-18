<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;
use App\Type\RelationElement;

class StartElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->getElement() instanceof RelationElement && $event->hasProperty('start')) {
            throw new ClientBadRequestException(detail: 'Manually setting the start property is forbidden.');
        }
    }
}
