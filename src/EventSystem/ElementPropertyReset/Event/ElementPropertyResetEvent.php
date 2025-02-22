<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReset\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;

class ElementPropertyResetEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @var string[]
     */
    private array $propertyNamesWhichAreKept = [];

    public function __construct(
        private NodeElementInterface|RelationElementInterface $element,
    ) {
    }

    public function getElement(): NodeElementInterface|RelationElementInterface
    {
        return $this->element;
    }

    public function addPropertyNameToBeKept(string $propertyName): static
    {
        $this->propertyNamesWhichAreKept[] = $propertyName;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getPropertyNamesWhichAreKept(): array
    {
        return $this->propertyNamesWhichAreKept;
    }
}
