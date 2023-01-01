<?php

namespace App\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use App\Type\NodeElement;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class NodeElementDefragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    private NodeElementInterface $nodeElement;

    public function __construct(
        private NodeInterface $cypherFragment,
        private ?DocumentInterface $documentFragment,
        private mixed $fileFragment = null
    ) {
        $this->nodeElement = new NodeElement();
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

    public function getCypherFragment(): NodeInterface
    {
        return $this->cypherFragment;
    }

    public function setCypherFragment(NodeInterface $cypherFragment): self
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
