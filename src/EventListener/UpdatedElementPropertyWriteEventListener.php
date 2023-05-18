<?php

namespace App\EventListener;

use App\Event\ElementPropertyWriteEvent;
use App\Exception\ClientBadRequestException;

class UpdatedElementPropertyWriteEventListener
{
    public function onElementPropertyWriteEvent(ElementPropertyWriteEvent $event): void
    {
        if ($event->hasProperty('updated')) {
            throw new ClientBadRequestException(detail: 'Manually setting the updated property is forbidden.');
        }
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $event->addProperty('updated', $now);
    }
}
