<?php

declare(strict_types=1);

namespace App\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use DateTime;
use DateTimeInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class DateTimeNormalizedValueToRawValueEventListener
{
    #[AsEventListener(priority: 16)]
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
