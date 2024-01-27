<?php

namespace App\EventSystem\ElementDefragmentize\Event;

use App\Contract\EventInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class RelationElementDefragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private RelationElementInterface $relationElement,
        private RelationInterface $cypherFragment,
        private ?DocumentInterface $documentFragment,
        private mixed $fileFragment = null
    ) {
    }

    public function getRelationElement(): RelationElementInterface
    {
        return $this->relationElement;
    }

    public function getCypherFragment(): RelationInterface
    {
        return $this->cypherFragment;
    }

    public function getDocumentFragment(): ?DocumentInterface
    {
        return $this->documentFragment;
    }

    public function getFileFragment(): mixed
    {
        return $this->fileFragment;
    }
}
