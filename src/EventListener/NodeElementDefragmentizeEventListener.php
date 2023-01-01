<?php

namespace App\EventListener;

use App\Event\NodeElementDefragmentizeEvent;
use Ramsey\Uuid\Rfc4122\UuidV4;

class NodeElementDefragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementDefragmentizeEvent(NodeElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $event->getNodeElement()
            ->setLabel($cypherFragment->getLabels()[0])
            ->setIdentifier(UuidV4::fromString($cypherFragment->getProperty('id')));
    }
}
