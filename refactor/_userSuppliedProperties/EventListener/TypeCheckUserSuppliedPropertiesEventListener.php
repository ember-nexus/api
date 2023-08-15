<?php

namespace App\EventListener;

use App\Event\CheckUserSuppliedPropertiesEvent;
use App\Exception\ClientBadRequestException;

class TypeCheckUserSuppliedPropertiesEventListener
{
    public function onCheckUserSuppliedPropertiesEvent(CheckUserSuppliedPropertiesEvent $event): void
    {
        if ($event->hasProperty('type')) {
            throw new ClientBadRequestException(detail: 'Manually setting the type property is forbidden.');
        }
    }
}
