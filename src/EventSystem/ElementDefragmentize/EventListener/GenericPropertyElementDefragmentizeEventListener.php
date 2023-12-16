<?php

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Helper\ReservedPropertyNameHelper;
use Laudis\Neo4j\Types\Date as LaudisDate;
use Laudis\Neo4j\Types\DateTime as LaudisDateTime;
use Laudis\Neo4j\Types\DateTimeZoneId as LaudisDateTimeZoneId;
use Laudis\Neo4j\Types\LocalDateTime as LaudisLocalDateTime;
use Laudis\Neo4j\Types\LocalTime as LaudisLocalTime;

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
            if ($value instanceof LaudisDateTimeZoneId) {
                $cypherProperties[$key] = $value->toDateTime();
            }
            if ($value instanceof LaudisDateTime) {
                $cypherProperties[$key] = $value->toDateTime();
            }
            if ($value instanceof LaudisDate) {
                $cypherProperties[$key] = $value->toDateTime();
            }
            if ($value instanceof LaudisLocalDateTime) {
                $cypherProperties[$key] = $value->toDateTime();
            }
            if ($value instanceof LaudisLocalTime) {
                $cypherProperties[$key] = $value->toArray();
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
