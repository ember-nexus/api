<?php

declare(strict_types=1);

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
        private string $labelOrType,
        private ?ElementInterface $element,
        private array $changedProperties
    ) {
    }

    public function getLabelOrType(): string
    {
        return $this->labelOrType;
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
