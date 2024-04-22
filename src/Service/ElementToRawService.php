<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementToRawService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function elementToRaw(NodeElementInterface|RelationElementInterface $element): array
    {
        $rawData = [
            'type' => null,
            'id' => $element->getId()?->toString(),
            'start' => null,
            'end' => null,
            'data' => [],
        ];

        if ($element instanceof NodeElementInterface) {
            $rawData['type'] = $element->getLabel();
            unset($rawData['start']);
            unset($rawData['end']);
        }
        if ($element instanceof RelationElementInterface) {
            $rawData['type'] = $element->getType();
            $rawData['start'] = $element->getStart()?->toString();
            $rawData['end'] = $element->getEnd()?->toString();
        }

        $elementPropertyReturnEvent = new ElementPropertyReturnEvent($element);
        $this->eventDispatcher->dispatch($elementPropertyReturnEvent);
        $normalizedProperties = $elementPropertyReturnEvent->getElementPropertiesWhichAreNotOnBlacklist();

        foreach ($normalizedProperties as $normalizedPropertyName => $normalizedPropertyValue) {
            $normalizedValueToRawValueEvent = new NormalizedValueToRawValueEvent($normalizedPropertyValue);
            $this->eventDispatcher->dispatch($normalizedValueToRawValueEvent);
            $rawData['data'][$normalizedPropertyName] = $normalizedValueToRawValueEvent->getRawValue();
        }

        return $rawData;
    }
}
