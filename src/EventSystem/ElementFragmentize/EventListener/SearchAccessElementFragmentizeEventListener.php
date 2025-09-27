<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class SearchAccessElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    #[AsEventListener]
    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
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
