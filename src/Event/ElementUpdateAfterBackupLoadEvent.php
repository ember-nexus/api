<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;

class ElementUpdateAfterBackupLoadEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private NodeElementInterface|RelationElementInterface $element
    ) {
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
