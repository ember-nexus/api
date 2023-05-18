<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;

class IdElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->hasProperty('id')) {
            throw new ClientBadRequestException(detail: 'Manually setting the id property is forbidden.');
        }
    }
}
