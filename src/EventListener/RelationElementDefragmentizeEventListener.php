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

        $identifier = $cypherFragment->getProperty('id');
        if (null === $identifier) {
            throw new \InvalidArgumentException();
        }
        $identifier = UuidV4::fromString($identifier);

        $start = $cypherFragment->getStartNode();
        if (null === $start) {
            throw new \InvalidArgumentException();
        }
        $start = $start->getProperty('id');
        if (null === $start) {
            throw new \InvalidArgumentException();
        }
        $start = UuidV4::fromString($start);

        $end = $cypherFragment->getEndNode();
        if (null === $end) {
            throw new \InvalidArgumentException();
        }
        $end = $end->getProperty('id');
        if (null === $end) {
            throw new \InvalidArgumentException();
        }
        $end = UuidV4::fromString($end);

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         *
         * @phpstan-ignore-next-line
         */
        $event->getRelationElement()
            ->setType($cypherFragment->getType())
            ->setIdentifier($identifier)
            ->setStart($start)
            ->setEnd($end);
    }
}
