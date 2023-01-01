<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;
use App\Type\FragmentGroup;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoDataStructures\Type\Document;

class RelationElementFragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    private RelationInterface $cypherFragment;
    private DocumentInterface $documentFragment;
    private mixed $fileFragment = null;

    public function __construct(
        private RelationElementInterface $nodeElement
    ) {
        $this->cypherFragment = new Relation();
        $this->documentFragment = new Document();
    }

    public function getAsFragmentGroup(): FragmentGroup
    {
        return (new FragmentGroup())
            ->setCypherFragment($this->cypherFragment)
            ->setDocumentFragment($this->documentFragment);
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

    public function getDocumentFragment(): DocumentInterface
    {
        return $this->documentFragment;
    }

    public function setDocumentFragment(DocumentInterface $documentFragment): self
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

    public function getRelationElement(): RelationElementInterface
    {
        return $this->nodeElement;
    }

    public function setRelationElement(RelationElementInterface $nodeElement): self
    {
        $this->nodeElement = $nodeElement;

        return $this;
    }
}
