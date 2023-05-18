<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;

class TypeElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->hasProperty('type')) {
            throw new ClientBadRequestException(detail: 'Manually setting the type property is forbidden.');
        }
    }
}
