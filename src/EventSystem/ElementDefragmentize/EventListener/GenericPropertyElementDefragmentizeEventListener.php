<?php

declare(strict_types=1);

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Helper\ReservedPropertyNameHelper;
use Laudis\Neo4j\Types\Date as LaudisDate;
use Laudis\Neo4j\Types\DateTime as LaudisDateTime;
use Laudis\Neo4j\Types\DateTimeZoneId as LaudisDateTimeZoneId;
use Laudis\Neo4j\Types\LocalDateTime as LaudisLocalDateTime;
use Laudis\Neo4j\Types\LocalTime as LaudisLocalTime;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class GenericPropertyElementDefragmentizeEventListener
{
    public function __construct()
    {
    }

    #[AsEventListener]
    public function onNodeElementDefragmentizeEvent(NodeElementDefragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onRelationElementDefragmentizeEvent(RelationElementDefragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
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
            foreach ($documentProperties as $key => $value) {
                if (($value instanceof BSONArray) || ($value instanceof BSONDocument)) {
                    $documentProperties[$key] = $value->getArrayCopy();
                }
            }
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
