<?php

namespace App\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use DateTime;
use DateTimeInterface;

class DateTimeNormalizedValueToRawValueEventListener
{
    public function onNormalizedValueToRawValueEvent(NormalizedValueToRawValueEvent $event): void
    {
        $normalizedValue = $event->getNormalizedValue();
        if (!($normalizedValue instanceof DateTimeInterface)) {
            return;
        }
        $event->setRawValue($normalizedValue->format(DateTime::ATOM));
        $event->stopPropagation();
    }
}
