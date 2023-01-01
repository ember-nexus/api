<?php

namespace App\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementToRawEvent;
use App\Helper\ReservedPropertyNameHelper;

class ElementToRawEventListener
{
    public function __construct()
    {
    }

    public function onElementToRawEvent(ElementToRawEvent $event): void
    {
        $element = $event->getElement();
        $metadata = [
            'id' => $element->getIdentifier()->toString(),
            'type' => '',
        ];
        if ($element instanceof NodeElementInterface) {
            $metadata['type'] = $element->getLabel();
        }
        if ($element instanceof RelationElementInterface) {
            $metadata['type'] = $element->getType();
            $metadata['startNode'] = $element->getStartNode()->toString();
            $metadata['endNode'] = $element->getEndNode()->toString();
        }
        $properties = $element->getProperties();
        $properties = ReservedPropertyNameHelper::removeReservedPropertyNamesFromArray($properties);
        ksort($properties);
        $rawData = array_merge($metadata, $properties);
        $event->setRawData($rawData);
        $event->stopPropagation();
    }
}
