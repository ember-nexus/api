<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use App\Type\FragmentGroup;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoDataStructures\Type\Document;

class NodeElementFragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    private NodeInterface $cypherFragment;
    private DocumentInterface $documentFragment;
    private mixed $fileFragment = null;

    public function __construct(
        private NodeElementInterface $nodeElement
    ) {
        $this->cypherFragment = new Node();
        $this->documentFragment = new Document();
    }

    public function getAsFragmentGroup(): FragmentGroup
    {
        return (new FragmentGroup())
            ->setCypherFragment($this->cypherFragment)
            ->setDocumentFragment($this->documentFragment);
    }

    public function getCypherFragment(): NodeInterface
    {
        return $this->cypherFragment;
    }

    public function setCypherFragment(NodeInterface $cypherFragment): self
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

    public function getNodeElement(): NodeElementInterface
    {
        return $this->nodeElement;
    }

    public function setNodeElement(NodeElementInterface $nodeElement): self
    {
        $this->nodeElement = $nodeElement;

        return $this;
    }
}
