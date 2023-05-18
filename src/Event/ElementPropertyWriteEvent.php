<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\HasPropertiesInterface;
use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Trait\PropertiesTrait;
use App\Trait\StoppableEventTrait;

class ElementPropertyWriteEvent implements EventInterface, HasPropertiesInterface
{
    use StoppableEventTrait;
    use PropertiesTrait;

    public function __construct(
        private NodeElementInterface|RelationElementInterface $element,
        array $newProperties
    ) {
        $this->addProperties($newProperties);
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
