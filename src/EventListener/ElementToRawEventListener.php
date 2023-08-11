<?php

namespace App\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementToRawEvent;
use Laudis\Neo4j\Types\DateTimeZoneId;

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
            'id' => $element->getIdentifier()?->toString(),
        ];
        if ($element instanceof NodeElementInterface) {
            $rawData['type'] = $element->getLabel();
        }
        if ($element instanceof RelationElementInterface) {
            $rawData['type'] = $element->getType();
            $rawData['start'] = $element->getStart()?->toString();
            $rawData['end'] = $element->getEnd()?->toString();
        }
        $properties = $element->getProperties();
        foreach ($properties as $key => $value) {
            if ($value instanceof DateTimeZoneId) {
                $properties[$key] = $value->toDateTime()->format(\DateTime::ATOM);
            }
        }
        ksort($properties);
        $rawData['data'] = $properties;
        $event->setRawData($rawData);
        $event->stopPropagation();
    }
}
