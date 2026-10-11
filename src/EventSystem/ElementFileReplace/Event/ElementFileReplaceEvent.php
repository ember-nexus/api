<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFileReplace\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use Ramsey\Uuid\UuidInterface;

class ElementFileReplaceEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private UuidInterface $elementId,
    ) {
    }

    public function getElementId(): UuidInterface
    {
        return $this->elementId;
    }
}
