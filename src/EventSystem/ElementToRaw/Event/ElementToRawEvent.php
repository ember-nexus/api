<?php

namespace App\EventSystem\ElementToRaw\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;

class ElementToRawEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @var array<string, mixed>
     */
    private array $rawData = [];

    public function __construct(
        private NodeElementInterface|RelationElementInterface $element
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawData(): array
    {
        return $this->rawData;
    }

    /**
     * @param array<string, mixed> $rawData
     */
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
