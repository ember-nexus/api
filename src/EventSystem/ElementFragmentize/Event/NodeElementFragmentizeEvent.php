<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

class NodeElementFragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private NodeElementInterface $nodeElement,
        private NodeInterface $cypherFragment,
        private MongoDocumentInterface $mongoFragment,
        private ElasticDocumentInterface $elasticFragment,
        private mixed $fileFragment,
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

    public function getMongoFragment(): MongoDocumentInterface
    {
        return $this->mongoFragment;
    }

    public function getElasticFragment(): ElasticDocumentInterface
    {
        return $this->elasticFragment;
    }

    public function getFileFragment(): mixed
    {
        return $this->fileFragment;
    }
}
