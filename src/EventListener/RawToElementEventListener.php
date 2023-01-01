<?php

namespace App\EventListener;

use App\Event\RawToElementEvent;
use App\Helper\ReservedPropertyNameHelper;
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
        if (array_key_exists('startNode', $rawData) && !array_key_exists('endNode', $rawData)) {
            throw new \Exception('Using the property startNode requires setting endNode too');
        }
        if (!array_key_exists('startNode', $rawData) && array_key_exists('endNode', $rawData)) {
            throw new \Exception('Using the property endNode requires setting startNode too');
        }
        if (array_key_exists('startNode', $rawData) && array_key_exists('endNode', $rawData)) {
            $element = new RelationElement();
            $element->setStartNode(UuidV4::fromString($rawData['startNode']));
            $element->setEndNode(UuidV4::fromString($rawData['endNode']));
            $element->setType($rawData['type']);
        }
        if (array_key_exists('id', $rawData)) {
            $element->setIdentifier(UuidV4::fromString($rawData['id']));
        }
        $rawProperties = $rawData;
        $rawProperties = ReservedPropertyNameHelper::removeReservedPropertyNamesFromArray($rawProperties);
        foreach ($rawProperties as $name => $value) {
            $element->addProperty($name, $value);
        }
        $event->setElement($element);
        $event->stopPropagation();
    }
}
