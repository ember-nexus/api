<?php

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Exception\ServerException;
use Laudis\Neo4j\Types\DateTimeZoneId;
use MongoDB\BSON\UTCDateTime;

class UpdatedPropertyElementFragmentizeEventListener
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
        if (!$element->hasProperty('updated')) {
            throw new ServerException(detail: 'Server must set updated property before persisting element');
        }
        $updated = $element->getProperty('updated');
        if ($updated instanceof DateTimeZoneId) {
            $updated = $updated->toDateTime();
        }
        if (!($updated instanceof \DateTimeInterface)) {
            throw new \Exception("Unable to get datetime info from updated property of type '".get_class($updated)."'.");
        }
        $cypherFragment->addProperty('updated', $updated);
        $mongoFragment->addProperty('updated', new UTCDateTime($updated));
        $elasticFragment->addProperty('updated', $updated->format('Uu'));
    }
}
