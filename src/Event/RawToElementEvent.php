<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;

class RawToElementEvent implements EventInterface
{
    use StoppableEventTrait;

    private NodeElementInterface|RelationElementInterface|null $element = null;

    /**
     * @param array<string, mixed> $rawData
     */
    public function __construct(
        private array $rawData
    ) {
    }

    public function getElement(): RelationElementInterface|NodeElementInterface|null
    {
        return $this->element;
    }

    public function setElement(RelationElementInterface|NodeElementInterface|null $element): void
    {
        $this->element = $element;
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function setRawData(array $rawData): void
    {
        $this->rawData = $rawData;
    }
}
