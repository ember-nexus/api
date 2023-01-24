<?php

namespace App\EventListener;

use App\Event\RawToElementEvent;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Ramsey\Uuid\Rfc4122\UuidV4;

class RawToElementEventListener
{
    public function __construct()
    {
    }

    public function onRawToElementEvent(RawToElementEvent $event): void
    {
        $element = new NodeElement();
        $rawData = $event->getRawData();
        $element->setLabel($rawData['type']);
        if (array_key_exists('start', $rawData) && !array_key_exists('end', $rawData)) {
            throw new \Exception('Using the property start requires setting end too');
        }
        if (!array_key_exists('start', $rawData) && array_key_exists('end', $rawData)) {
            throw new \Exception('Using the property end requires setting start too');
        }
        if (array_key_exists('start', $rawData) && array_key_exists('end', $rawData)) {
            $element = new RelationElement();
            $element->setStartNode(UuidV4::fromString($rawData['start']));
            $element->setEndNode(UuidV4::fromString($rawData['end']));
            $element->setType($rawData['type']);
        }
        if (array_key_exists('id', $rawData)) {
            $element->setIdentifier(UuidV4::fromString($rawData['id']));
        }
        if (array_key_exists('data', $rawData)) {
            $element->addProperties($rawData['data']);
        }
        $event->setElement($element);
        $event->stopPropagation();
    }
}
