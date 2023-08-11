<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;
use App\Exception\ServerException;
use Laudis\Neo4j\Types\DateTimeZoneId;
use MongoDB\BSON\UTCDateTime;

class CreatedPropertyElementFragmentizeEventListener
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
        if (!$element->hasProperty('created')) {
            throw new ServerException(detail: 'Server must set created property before persisting element');
        }
        $created = $element->getProperty('created');
        if ($created instanceof DateTimeZoneId) {
            $created = $created->toDateTime();
        }
        if (!($created instanceof \DateTimeInterface)) {
            throw new \Exception("Unable to get datetime info from created property of type '".get_class($created)."'.");
        }
        $cypherFragment->addProperty('created', $created);
        $mongoFragment->addProperty('created', new UTCDateTime($created));
        $elasticFragment->addProperty('created', $created->format('Uu'));
    }
}
