<?php

namespace App\EventSystem\ElementDefragmentize\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class NodeElementDefragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private NodeElementInterface $nodeElement,
        private NodeInterface $cypherFragment,
        private ?DocumentInterface $documentFragment,
        private mixed $fileFragment = null
    ) {
    }

    public function getNodeElement(): NodeElementInterface
    {
        return $this->nodeElement;
    }

    public function getCypherFragment(): NodeInterface
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
