<?php

declare(strict_types=1);

namespace App\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class MongoDBNormalizedValueToRawValueEventListener
{
    #[AsEventListener(priority: 15)]
    public function onNormalizedValueToRawValueEvent(NormalizedValueToRawValueEvent $event): void
    {
        $normalizedValue = $event->getNormalizedValue();
        if (!($normalizedValue instanceof BSONArray) && !($normalizedValue instanceof BSONDocument)) {
            return;
        }
        $event->setRawValue($normalizedValue->getArrayCopy());
        $event->stopPropagation();
    }
}
