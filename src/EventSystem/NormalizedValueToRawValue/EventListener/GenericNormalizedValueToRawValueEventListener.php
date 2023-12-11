<?php

namespace App\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;

class GenericNormalizedValueToRawValueEventListener
{
    public function onNormalizedValueToRawValueEvent(NormalizedValueToRawValueEvent $event): void
    {
        $normalizedValue = $event->getNormalizedValue();
        if (
            is_array($normalizedValue)
            || is_numeric($normalizedValue)
            || is_bool($normalizedValue)
            || is_string($normalizedValue)
            || is_null($normalizedValue)
        ) {
            $event->setRawValue($normalizedValue);
            $event->stopPropagation();
        }
    }
}
