<?php

namespace App\EventListener;

use App\Event\CheckUserSuppliedPropertiesEvent;
use App\Exception\ClientBadRequestException;

class IdCheckUserSuppliedPropertiesEventListener
{
    public function onCheckUserSuppliedPropertiesEvent(CheckUserSuppliedPropertiesEvent $event): void
    {
        if ($event->getElement()->getIdentifier() && $event->hasProperty('id')) {
            throw new ClientBadRequestException(detail: 'Manually changing the id property is forbidden.');
        }
    }
}
