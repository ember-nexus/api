<?php

namespace App\EventListener;

use App\Event\CheckUserSuppliedPropertiesEvent;
use App\Exception\ClientBadRequestException;
use App\Type\RelationElement;

class EndCheckUserSuppliedPropertiesEventListener
{
    public function onCheckUserSuppliedPropertiesEvent(CheckUserSuppliedPropertiesEvent $event): void
    {
        if ($event->getElement() instanceof RelationElement && $event->hasProperty('end')) {
            throw new ClientBadRequestException(detail: 'Manually setting the end property is forbidden.');
        }
    }
}
