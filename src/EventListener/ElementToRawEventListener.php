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
        $rawData = [
            'type' => '',
            'id' => $element->getIdentifier()->toString(),
        ];
        if ($element instanceof NodeElementInterface) {
            $rawData['type'] = $element->getLabel();
        }
        if ($element instanceof RelationElementInterface) {
            $rawData['type'] = $element->getType();
            $rawData['start'] = $element->getStartNode()->toString();
            $rawData['end'] = $element->getEndNode()->toString();
        }
        $properties = $element->getProperties();
        ksort($properties);
        $rawData['data'] = $properties;
        $event->setRawData($rawData);
        $event->stopPropagation();
    }
}
