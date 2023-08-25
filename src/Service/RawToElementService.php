<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;

class RawToElementService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function rawToElement(array $rawData): NodeElementInterface|RelationElementInterface
    {
        if (!array_key_exists('type', $rawData)) {
            throw new Exception("Unable to find required property 'type' in raw data.");
        }
        $type = $rawData['type'];

        if (!array_key_exists('id', $rawData)) {
            throw new Exception("Unable to find required property 'id' in raw data.");
        }
        $id = Uuid::fromString($rawData['id']);

        $start = null;
        if (array_key_exists('start', $rawData)) {
            $start = Uuid::fromString($rawData['start']);
        }

        $end = null;
        if (array_key_exists('end', $rawData)) {
            $end = Uuid::fromString($rawData['end']);
        }

        $rawProperties = [];
        if (array_key_exists('data', $rawData)) {
            $rawProperties = $rawData['data'];
        }
        /**
         * @var array<string, mixed> $rawProperties
         */
        $normalizedProperties = [];
        foreach ($rawProperties as $rawPropertyName => $rawPropertyValue) {
            $rawValueToNormalizedValueEvent = new RawValueToNormalizedValueEvent($rawPropertyValue);
            $this->eventDispatcher->dispatch($rawValueToNormalizedValueEvent);
            $normalizedProperties[$rawPropertyName] = $rawValueToNormalizedValueEvent->getNormalizedValue();
        }

        if (null !== $start && null !== $end) {
            $element = new RelationElement();
            $element->setStart($start);
            $element->setEnd($end);
            $element->setType($type);
        } else {
            $element = new NodeElement();
            $element->setLabel($type);
        }
        $element->addProperties($normalizedProperties);
        $element->setIdentifier($id);

        return $element;
    }
}
