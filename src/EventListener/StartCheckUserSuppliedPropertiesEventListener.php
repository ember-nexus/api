<?php

namespace App\EventListener;

use App\Event\CheckUserSuppliedPropertiesEvent;
use App\Exception\ClientBadRequestException;
use App\Type\RelationElement;

class StartCheckUserSuppliedPropertiesEventListener
{
    public function onCheckUserSuppliedPropertiesEvent(CheckUserSuppliedPropertiesEvent $event): void
    {
        if ($event->getElement() instanceof RelationElement && $event->hasProperty('start')) {
            throw new ClientBadRequestException(detail: 'Manually setting the start property is forbidden.');
        }
    }
}
