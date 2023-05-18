<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;

class CreatedElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->hasProperty('created')) {
            throw new ClientBadRequestException(detail: 'Manually setting the created property is forbidden.');
        }
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $event->addProperty('created', $now);
    }
}
