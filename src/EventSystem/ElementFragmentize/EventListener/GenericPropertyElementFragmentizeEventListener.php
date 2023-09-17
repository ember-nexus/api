<?php

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use DateTimeInterface;
use Laudis\Neo4j\Types\DateTimeZoneId;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class GenericPropertyElementFragmentizeEventListener
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
        foreach ($element->getProperties() as $name => $value) {
            $elasticFragment->addProperty($name, $value);
            if (is_array($value)) {
                $mongoFragment->addProperty($name, $value);
                continue;
            }
            if ($value instanceof DateTimeInterface) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->format('Uu'));
                continue;
            }
            if ($value instanceof DateTimeZoneId) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toDateTime()->format('Uu'));
                continue;
            }
            if (is_object($value)) {
                $mongoFragment->addProperty($name, $value);
                continue;
            }
            if (is_numeric($value)) {
                $cypherFragment->addProperty($name, $value);
                continue;
            }
            if (is_bool($value)) {
                $cypherFragment->addProperty($name, $value);
                continue;
            }
            if (is_string($value)) {
                if (strlen($value) > 1024) {
                    $mongoFragment->addProperty($name, $value);
                } else {
                    $cypherFragment->addProperty($name, $value);
                }
                continue;
            }
            if (is_null($value)) {
                $mongoFragment->addProperty($name, $value);
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value);
                continue;
            }
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate(sprintf("Unknown data type with value '%s'.", $value));
        }
    }
}
