<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;
use App\Type\RelationElement;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class RelationElementDefragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    private RelationElementInterface $relationElement;

    public function __construct(
        private RelationInterface $cypherFragment,
        private ?DocumentInterface $documentFragment,
        private mixed $fileFragment = null
    ) {
        $this->relationElement = new RelationElement();
    }

    public function getRelationElement(): RelationElementInterface
    {
        return $this->relationElement;
    }

    public function setRelationElement(RelationElementInterface $relationElement): self
    {
        $this->relationElement = $relationElement;

        return $this;
    }

    public function getCypherFragment(): RelationInterface
    {
        return $this->cypherFragment;
    }

    public function setCypherFragment(RelationInterface $cypherFragment): self
    {
        $this->cypherFragment = $cypherFragment;

        return $this;
    }

    public function getDocumentFragment(): ?DocumentInterface
    {
        return $this->documentFragment;
    }

    public function setDocumentFragment(?DocumentInterface $documentFragment): self
    {
        $this->documentFragment = $documentFragment;

        return $this;
    }

    public function getFileFragment(): mixed
    {
        return $this->fileFragment;
    }

    public function setFileFragment(mixed $fileFragment): self
    {
        $this->fileFragment = $fileFragment;

        return $this;
    }
}
