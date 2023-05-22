<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;

class SearchAccessElementFragmentizeEventListener
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
        $elasticFragment = $event->getElasticFragment();
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        } else {
            $element = $event->getRelationElement();
        }
        if ($element->hasProperty('_groupsWithSearchAccess')) {
            $elasticFragment->addProperty('_groupsWithSearchAccess', $element->getProperty('_groupsWithSearchAccess'));
        }
        if ($element->hasProperty('_usersWithSearchAccess')) {
            $elasticFragment->addProperty('_usersWithSearchAccess', $element->getProperty('_usersWithSearchAccess'));
        }
        $element->removeProperty('_groupsWithSearchAccess');
        $element->removeProperty('_usersWithSearchAccess');
    }
}
