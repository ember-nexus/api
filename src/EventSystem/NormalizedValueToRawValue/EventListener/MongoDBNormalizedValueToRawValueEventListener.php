<?php

declare(strict_types=1);

namespace App\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use MongoDB\Model\BSONArray;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class MongoDBNormalizedValueToRawValueEventListener
{
    #[AsEventListener(priority: 15)]
    public function onNormalizedValueToRawValueEvent(NormalizedValueToRawValueEvent $event): void
    {
        $normalizedValue = $event->getNormalizedValue();
        if (!($normalizedValue instanceof BSONArray)) {
            return;
        }
        $event->setRawValue($normalizedValue->getArrayCopy());
        $event->stopPropagation();
    }
}
