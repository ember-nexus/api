<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;

class NamePropertyElementFragmentizeEventListener
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
        $documentFragment = $event->getDocumentFragment();
        $element = null;
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        }
        if ($event instanceof RelationElementFragmentizeEvent) {
            $element = $event->getRelationElement();
        }
        if ($element->hasProperty('name')) {
            $cypherFragment->addProperty('name', $element->getProperty('name'));
            $documentFragment->addProperty('name', $element->getProperty('name'));
        }
    }
}
