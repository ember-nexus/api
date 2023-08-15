<?php

namespace App\EventSystem\ElementPropertyChange\Event;

use App\Contract\ElementInterface;
use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;

class ElementPropertyChangeEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @param array<string, mixed> $changedProperties
     */
    public function __construct(
        private string $type,
        private ?ElementInterface $element,
        private array $changedProperties
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getElement(): ?ElementInterface
    {
        return $this->element;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChangedProperties(): array
    {
        return $this->changedProperties;
    }
}
