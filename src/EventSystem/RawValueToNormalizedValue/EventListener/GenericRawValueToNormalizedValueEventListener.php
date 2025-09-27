<?php

declare(strict_types=1);

namespace App\EventSystem\RawValueToNormalizedValue\EventListener;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class GenericRawValueToNormalizedValueEventListener
{
    #[AsEventListener(priority: 0)]
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
