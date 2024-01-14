<?php

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
    ) {
    }

    public function getCypherFragment(): RelationInterface|NodeInterface
    {
        return $this->cypherFragment;
    }

    public function setCypherFragment(RelationInterface|NodeInterface $cypherFragment): self
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
}
