<?php

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use DateTimeInterface;
use Laudis\Neo4j\Types\DateTimeZoneId;
use MongoDB\BSON\UTCDateTime;

class CreatedPropertyElementFragmentizeEventListener
{
    public function __construct(
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory
    ) {
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
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Server must set created property before persisting element');
        }
        $created = $element->getProperty('created');
        if ($created instanceof DateTimeZoneId) {
            $created = $created->toDateTime();
        }
        if (!($created instanceof DateTimeInterface)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate("Unable to get datetime info from created property of type '".get_class($created)."'.");
        }
        $cypherFragment->addProperty('created', $created);
        $mongoFragment->addProperty('created', new UTCDateTime($created));
        $elasticFragment->addProperty('created', $created->format('Uu'));
    }
}
