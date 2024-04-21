<?php

declare(strict_types=1);

namespace App\EventSystem\RawValueToNormalizedValue\EventListener;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use DateTime;

class DateTimeRawValueToNormalizedValueEventListener
{
    public function onRawValueToNormalizedValueEvent(RawValueToNormalizedValueEvent $event): void
    {
        $rawValue = $event->getRawValue();
        if (!is_string($rawValue)) {
            return;
        }
        if (
            strlen($rawValue) < 22
            || strlen($rawValue) > 25
        ) {
            return;
        }
        $possibleDate = DateTime::createFromFormat(DateTime::ATOM, $rawValue);
        if (false === $possibleDate) {
            return;
        }
        $event->setNormalizedValue($possibleDate);
        $event->stopPropagation();
    }
}
