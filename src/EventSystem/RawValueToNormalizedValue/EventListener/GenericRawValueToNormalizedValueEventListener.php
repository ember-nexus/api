<?php

namespace App\EventSystem\RawValueToNormalizedValue\EventListener;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;

class GenericRawValueToNormalizedValueEventListener
{
    public function onRawValueToNormalizedValueEvent(RawValueToNormalizedValueEvent $event): void
    {
        $rawValue = $event->getRawValue();
        if (
            is_array($rawValue)
            || is_numeric($rawValue)
            || is_bool($rawValue)
            || is_string($rawValue)
            || is_null($rawValue)
        ) {
            $event->setNormalizedValue($rawValue);
            $event->stopPropagation();
        }
    }
}
