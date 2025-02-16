<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\Event;

use App\Contract\EventInterface;
use App\Contract\RelationElementInterface;
use App\Trait\StoppableEventTrait;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

class RelationElementFragmentizeEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private RelationElementInterface $nodeElement,
        private RelationInterface $cypherFragment,
        private MongoDocumentInterface $mongoFragment,
        private ElasticDocumentInterface $elasticFragment,
        private mixed $fileFragment,
    ) {
    }

    public function getRelationElement(): RelationElementInterface
    {
        return $this->nodeElement;
    }

    public function getCypherFragment(): RelationInterface
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
