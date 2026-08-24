<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Writes 'hasFile' to all three backing stores unconditionally, mirroring NamePropertyElementFragmentizeEventListener.
 * Unlike 'file' (a nested object, only stored in MongoDB), 'hasFile' is a plain boolean, so it can live on the
 * Cypher node/relation itself and in Elasticsearch - which is what lets search filter on, and listings display,
 * whether an element has a file at all.
 */
class HasFilePropertyElementFragmentizeEventListener
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
