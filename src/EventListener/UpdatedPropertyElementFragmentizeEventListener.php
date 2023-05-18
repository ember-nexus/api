<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;
use App\Exception\ServerException;

class UpdatedPropertyElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(NodeElementFragmentizeEvent|RelationElementFragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        } else {
            $element = $event->getRelationElement();
        }
        if (!$element->hasProperty('updated')) {
            throw new ServerException(detail: 'Server must set updated property before persisting element');
        }
        $updated = $element->getProperty('updated');
        $cypherFragment->addProperty('updated', $updated);
        $mongoFragment->addProperty('updated', $updated);
        $elasticFragment->addProperty('updated', $updated);
    }
}
