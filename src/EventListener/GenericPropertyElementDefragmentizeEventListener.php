<?php

namespace App\EventListener;

use App\Event\NodeElementDefragmentizeEvent;
use App\Event\RelationElementDefragmentizeEvent;
use App\Helper\ReservedPropertyNameHelper;

class GenericPropertyElementDefragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementDefragmentizeEvent(NodeElementDefragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onRelationElementDefragmentizeEvent(RelationElementDefragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(NodeElementDefragmentizeEvent|RelationElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $documentFragment = $event->getDocumentFragment();
        if ($event instanceof NodeElementDefragmentizeEvent) {
            $element = $event->getNodeElement();
        } else {
            $element = $event->getRelationElement();
        }
        if ($documentFragment) {
            $documentProperties = $documentFragment->getProperties();
            $documentProperties = ReservedPropertyNameHelper::removeReservedPropertyNamesFromArray($documentProperties);
            $element->addProperties($documentProperties);
        }
        $cypherProperties = $cypherFragment->getProperties();
        $cypherProperties = ReservedPropertyNameHelper::removeReservedPropertyNamesFromArray($cypherProperties);
        $element->addProperties($cypherProperties);
    }
}
