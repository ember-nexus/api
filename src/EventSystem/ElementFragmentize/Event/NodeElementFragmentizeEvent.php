<?php

namespace App\EventSystem\ElementFragmentize\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use App\Type\FragmentGroup;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

class NodeElementFragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    private NodeInterface $cypherFragment;
    private MongoDocumentInterface $mongoFragment;
    private ElasticDocumentInterface $elasticFragment;
    private mixed $fileFragment = null;

    public function __construct(
        private NodeElementInterface $nodeElement
    ) {
        $this->cypherFragment = new Node();
        $this->mongoFragment = new MongoDocument();
        $this->elasticFragment = new ElasticDocument();
    }

    public function getAsFragmentGroup(): FragmentGroup
    {
        return new FragmentGroup(
            $this->cypherFragment,
            $this->mongoFragment,
            $this->elasticFragment
        );
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

    public function getMongoFragment(): MongoDocumentInterface
    {
        return $this->mongoFragment;
    }

    public function setMongoFragment(MongoDocumentInterface $mongoFragment): self
    {
        $this->mongoFragment = $mongoFragment;

        return $this;
    }

    public function getElasticFragment(): ElasticDocumentInterface
    {
        return $this->elasticFragment;
    }

    public function setElasticFragment(ElasticDocumentInterface $elasticFragment): self
    {
        $this->elasticFragment = $elasticFragment;

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
