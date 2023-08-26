<?php

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Ramsey\Uuid\Rfc4122\UuidV4;

class RelationElementDefragmentizeEventListener
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function onRelationElementDefragmentizeEvent(RelationElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();

        $identifier = $cypherFragment->getProperty('id');
        if (null === $identifier) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires cypher fragment to contain valid UUID.');
        }
        $identifier = UuidV4::fromString($identifier);

        $start = $cypherFragment->getStartNode();
        if (null === $start) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires cypher fragment to contain valid start node.');
        }
        $start = $start->getProperty('id');
        if (null === $start) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires start node to have valid UUID.');
        }
        $start = UuidV4::fromString($start);

        $end = $cypherFragment->getEndNode();
        if (null === $end) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires cypher fragment to contain valid end node.');
        }
        $end = $end->getProperty('id');
        if (null === $end) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires end node to have valid UUID.');
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
