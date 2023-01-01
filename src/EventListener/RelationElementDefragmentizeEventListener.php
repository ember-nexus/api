<?php

namespace App\EventListener;

use App\Event\RelationElementDefragmentizeEvent;
use Ramsey\Uuid\Rfc4122\UuidV4;

class RelationElementDefragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onRelationElementDefragmentizeEvent(RelationElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $event->getRelationElement()
            ->setType($cypherFragment->getType())
            ->setIdentifier(UuidV4::fromString($cypherFragment->getProperty('id')))
            ->setStartNode(UuidV4::fromString($cypherFragment->getStartNode()->getProperty('id')))
            ->setEndNode(UuidV4::fromString($cypherFragment->getEndNode()->getProperty('id')));
    }
}
