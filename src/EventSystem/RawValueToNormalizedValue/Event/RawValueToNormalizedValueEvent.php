<?php

namespace App\EventSystem\RawValueToNormalizedValue\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;

class RawValueToNormalizedValueEvent implements EventInterface
{
    use StoppableEventTrait;

    private mixed $normalizedValue;

    public function __construct(
        private readonly mixed $rawValue
    ) {
    }

    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }

    public function getNormalizedValue(): mixed
    {
        return $this->normalizedValue;
    }

    public function setNormalizedValue(mixed $normalizedValue): self
    {
        $this->normalizedValue = $normalizedValue;

        return $this;
    }
}
