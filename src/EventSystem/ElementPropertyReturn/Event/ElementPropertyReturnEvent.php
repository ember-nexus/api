<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReturn\Event;

use App\Contract\ElementInterface;
use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;

class ElementPropertyReturnEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @var string[]
     */
    private array $blockedProperties = [];

    public function __construct(
        private ElementInterface $element
    ) {
    }

    public function getElement(): ?ElementInterface
    {
        return $this->element;
    }

    /**
     * @return string[]
     */
    public function getBlockedProperties(): array
    {
        return $this->blockedProperties;
    }

    /**
     * @return array<string, mixed>
     */
    public function getElementPropertiesWhichAreNotOnBlacklist(): array
    {
        $properties = $this->element->getProperties();
        foreach ($this->blockedProperties as $blockedPropertyName) {
            if (array_key_exists($blockedPropertyName, $properties)) {
                unset($properties[$blockedPropertyName]);
            }
        }

        return $properties;
    }

    public function addBlockedProperty(string $name): self
    {
        if (!in_array($name, $this->blockedProperties)) {
            $this->blockedProperties[] = $name;
        }

        return $this;
    }
}
