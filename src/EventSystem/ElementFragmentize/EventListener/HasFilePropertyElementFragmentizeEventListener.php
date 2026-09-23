<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Stores 'hasFile' in all three databases, so that Cypher and Elasticsearch queries can filter on it.
 */
class HasFilePropertyElementFragmentizeEventListener
{
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
        $cypherFragment = $event->getCypherFragment();
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        } else {
            $element = $event->getRelationElement();
        }
        if ($element->hasProperty('hasFile')) {
            $cypherFragment->addProperty('hasFile', $element->getProperty('hasFile'));
            $mongoFragment->addProperty('hasFile', $element->getProperty('hasFile'));
            $elasticFragment->addProperty('hasFile', $element->getProperty('hasFile'));
        }
    }
}
