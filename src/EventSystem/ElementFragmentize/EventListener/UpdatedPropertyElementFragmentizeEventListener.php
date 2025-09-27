<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use DateTime;
use DateTimeInterface;
use Laudis\Neo4j\Types\DateTimeZoneId;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UpdatedPropertyElementFragmentizeEventListener
{
    public function __construct(
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory,
    ) {
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
        if (!$element->hasProperty('updated')) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Server must set updated property before persisting element.');
        }
        $updated = $element->getProperty('updated');
        if ($updated instanceof DateTimeZoneId) {
            $updated = $updated->toDateTime();
        }
        if (!($updated instanceof DateTimeInterface)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate("Unable to get datetime info from updated property of type '".get_class($updated)."'.", ['updated' => $updated]);
        }
        $cypherFragment->addProperty('updated', $updated);
        $mongoFragment->addProperty('updated', new UTCDateTime($updated));
        $elasticFragment->addProperty('updated', $updated->format(DateTime::ATOM));
    }
}
