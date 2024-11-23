<?php

declare(strict_types=1);

namespace App\EventSystem\NormalizedValueToRawValue\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;

class NormalizedValueToRawValueEvent implements EventInterface
{
    use StoppableEventTrait;

    private mixed $rawValue;

    public function __construct(
        private readonly mixed $normalizedValue,
    ) {
    }

    public function getNormalizedValue(): mixed
    {
        return $this->normalizedValue;
    }

    public function getRawValue(): mixed
    {
        return $this->rawValue;
    }

    public function setRawValue(mixed $rawValue): self
    {
        $this->rawValue = $rawValue;

        return $this;
    }
}
