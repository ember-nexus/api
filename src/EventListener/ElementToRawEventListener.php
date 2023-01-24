<?php

namespace App\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementToRawEvent;

class ElementToRawEventListener
{
    public function __construct()
    {
    }

    public function onElementToRawEvent(ElementToRawEvent $event): void
    {
        $element = $event->getElement();
        $properties = $element->getProperties();
        ksort($properties);
        $rawData = [
            'type' => '',
            'id' => $element->getIdentifier()->toString(),
            'data' => $properties,
        ];
        if ($element instanceof NodeElementInterface) {
            $rawData['type'] = $element->getLabel();
        }
        if ($element instanceof RelationElementInterface) {
            $rawData['type'] = $element->getType();
            $rawData['startNode'] = $element->getStartNode()->toString();
            $rawData['endNode'] = $element->getEndNode()->toString();
        }
        $event->setRawData($rawData);
        $event->stopPropagation();
    }
}
