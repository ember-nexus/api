<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;

class ElementToRawEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @param array<string, mixed> $rawData
     */
    private array $rawData = [];

    public function __construct(
        private NodeElementInterface|RelationElementInterface $element
    ) {
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): void
    {
        $this->rawData = $rawData;
    }

    public function getElement(): RelationElementInterface|NodeElementInterface
    {
        return $this->element;
    }

    public function setElement(RelationElementInterface|NodeElementInterface $element): void
    {
        $this->element = $element;
    }
}
