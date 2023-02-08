<?php

namespace App\EventListener;

use App\Event\RelationElementFragmentizeEvent;
use Syndesi\CypherDataStructures\Type\Node;

class RelationElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $relationElement = $event->getRelationElement();

        $relationUuid = $relationElement->getIdentifier();
        if (null === $relationUuid) {
            throw new \LogicException();
        }
        $relationUuid = $relationUuid->toString();

        $relationType = $relationElement->getType();
        if (null === $relationType) {
            throw new \LogicException();
        }

        $startUuid = $relationElement->getStart();
        if (null === $startUuid) {
            throw new \LogicException();
        }
        $startUuid = $startUuid->toString();

        $endUuid = $relationElement->getEnd();
        if (null === $endUuid) {
            throw new \LogicException();
        }
        $endUuid = $endUuid->toString();

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $event->getCypherFragment()
            ->setType($relationElement->getType())
            ->setStartNode(
                (new Node())
                    ->addProperty('id', $startUuid)
                    ->addIdentifier('id')
            )
            ->setEndNode(
                (new Node())
                    ->addProperty('id', $endUuid)
                    ->addIdentifier('id')
            )
            ->addProperty('id', $relationUuid)
            ->addIdentifier('id');
        $event->getMongoFragment()
            ->setCollection($relationType)
            ->setIdentifier($relationUuid);
        $event->getElasticFragment()
            ->setIndex(sprintf(
                'relation_%s',
                strtolower($relationType)
            ))
            ->setIdentifier($relationUuid);
    }
}
