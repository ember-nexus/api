<?php

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Helper\ReservedPropertyNameHelper;
use Laudis\Neo4j\Types\DateTimeZoneId;

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
        foreach ($cypherProperties as $key => $value) {
            if ($value instanceof DateTimeZoneId) {
                $cypherProperties[$key] = $value->toDateTime();
            }
        }
        $element->addProperties($cypherProperties);
        foreach ($element->getProperties() as $key => $value) {
            if (null === $value) {
                $element->removeProperty($key);
            }
        }
    }
}
