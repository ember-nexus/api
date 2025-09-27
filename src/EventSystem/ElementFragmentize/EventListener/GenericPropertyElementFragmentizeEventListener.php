<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use DateTimeInterface;
use Laudis\Neo4j\Types\Date as LaudisDate;
use Laudis\Neo4j\Types\DateTime as LaudisDateTime;
use Laudis\Neo4j\Types\DateTimeZoneId as LaudisDateTimeZoneId;
use Laudis\Neo4j\Types\LocalDateTime as LaudisLocalDateTime;
use Laudis\Neo4j\Types\LocalTime as LaudisLocalTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class GenericPropertyElementFragmentizeEventListener
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
            if ($value instanceof LaudisDateTimeZoneId) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toDateTime()->format('Uu'));
                continue;
            }
            if ($value instanceof LaudisDateTime) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toDateTime()->format('Uu'));
                continue;
            }
            if ($value instanceof LaudisDate) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toDateTime()->format('Uu'));
                continue;
            }
            if ($value instanceof LaudisLocalDateTime) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toDateTime()->format('Uu'));
                continue;
            }
            if ($value instanceof LaudisLocalTime) {
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value->toArray());
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
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate(sprintf("Unknown data type with value '%s'.", $value), ['value' => $value]);
        }
    }
}
