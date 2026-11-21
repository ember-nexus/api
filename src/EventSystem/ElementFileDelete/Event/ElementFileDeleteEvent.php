<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFileDelete\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use App\Type\Uuid;

class ElementFileDeleteEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private Uuid $elementId,
    ) {
    }

    public function getElementId(): Uuid
    {
        return $this->elementId;
    }
}
