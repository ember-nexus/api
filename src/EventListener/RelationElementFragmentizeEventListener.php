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
        $event->getCypherFragment()
            ->setType($relationElement->getType())
            ->setStartNode(
                (new Node())
                    ->addProperty('id', $relationElement->getStartNode()->toString())
                    ->addIdentifier('id')
            )
            ->setEndNode(
                (new Node())
                    ->addProperty('id', $relationElement->getEndNode()->toString())
                    ->addIdentifier('id')
            )
            ->addProperty('id', $relationElement->getIdentifier()->toString())
            ->addIdentifier('id');
        $event->getMongoFragment()
            ->setCollection($relationElement->getType())
            ->setIdentifier($relationElement->getIdentifier()->toString());
        $event->getElasticFragment()
            ->setIndex(sprintf(
                'relation_%s',
                strtolower($relationElement->getType())
            ))
            ->setIdentifier($relationElement->getIdentifier()->toString());
    }
}
