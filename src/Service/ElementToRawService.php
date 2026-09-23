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
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param bool $applyPropertyBlacklist Whether to dispatch {@see ElementPropertyReturnEvent} and honor the
     *                                     property blacklists it collects (e.g. a Token's `hash` or a User's
     *                                     `_passwordHash`). These blacklists exist to keep such properties out of
     *                                     HTTP responses; callers that need the full, unfiltered element data for
     *                                     internal purposes (e.g. `backup:create`) must pass `false` so the
     *                                     resulting data can be restored without losing those properties.
     *
     * @return array<string, mixed>
     */
    public function elementToRaw(NodeElementInterface|RelationElementInterface $element, bool $applyPropertyBlacklist = true): array
    {
        $rawData = [
            'type' => null,
            'id' => $element->getId()?->toString(),
            'start' => null,
            'end' => null,
            'data' => [],
            'file' => null,
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

        if ($applyPropertyBlacklist) {
            $elementPropertyReturnEvent = new ElementPropertyReturnEvent($element);
            $this->eventDispatcher->dispatch($elementPropertyReturnEvent);
            $normalizedProperties = $elementPropertyReturnEvent->getElementPropertiesWhichAreNotOnBlacklist();
        } else {
            $normalizedProperties = $element->getProperties();
        }

        foreach ($normalizedProperties as $normalizedPropertyName => $normalizedPropertyValue) {
            $normalizedValueToRawValueEvent = new NormalizedValueToRawValueEvent($normalizedPropertyValue);
            $this->eventDispatcher->dispatch($normalizedValueToRawValueEvent);
            $rawData['data'][$normalizedPropertyName] = $normalizedValueToRawValueEvent->getRawValue();
        }

        if (array_key_exists('file', $rawData['data'])) {
            $rawData['file'] = $rawData['data']['file'];
            unset($rawData['data']['file']);
        } else {
            unset($rawData['file']);
        }

        return $rawData;
    }
}
