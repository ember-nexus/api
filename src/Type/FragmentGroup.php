<?php

declare(strict_types=1);

namespace App\Type;

use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

class FragmentGroup
{
    public function __construct(
        private NodeInterface|RelationInterface $cypherFragment,
        private MongoDocumentInterface $mongoFragment,
        private ElasticDocumentInterface $elasticFragment,
        private mixed $fileFragment,
    ) {
    }

    public function getCypherFragment(): RelationInterface|NodeInterface
    {
        return $this->cypherFragment;
    }

    public function setCypherFragment(RelationInterface|NodeInterface $cypherFragment): static
    {
        $this->cypherFragment = $cypherFragment;

        return $this;
    }

    public function getMongoFragment(): MongoDocumentInterface
    {
        return $this->mongoFragment;
    }

    public function setMongoFragment(MongoDocumentInterface $mongoFragment): static
    {
        $this->mongoFragment = $mongoFragment;

        return $this;
    }

    public function getElasticFragment(): ElasticDocumentInterface
    {
        return $this->elasticFragment;
    }

    public function setElasticFragment(ElasticDocumentInterface $elasticFragment): static
    {
        $this->elasticFragment = $elasticFragment;

        return $this;
    }

    public function getFileFragment(): mixed
    {
        return $this->fileFragment;
    }

    public function setFileFragment(mixed $fileFragment): static
    {
        $this->fileFragment = $fileFragment;

        return $this;
    }
}
