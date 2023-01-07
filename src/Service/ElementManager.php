<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Helper\Neo4jClientHelper;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

class ElementManager
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private ElementFragmentizeService $elementFragmentizeService,
        private ElementDefragmentizeService $elementDefragmentizeService,
        private Neo4jClientHelper $neo4jClientHelper
    ) {
    }

    public function create(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->create($fragmentGroup->getCypherFragment());
        $this->mongoEntityManager->create($fragmentGroup->getMongoFragment());
        $this->elasticEntityManager->create($fragmentGroup->getElasticFragment());
    }

    public function merge(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->merge($fragmentGroup->getCypherFragment());
        $this->mongoEntityManager->merge($fragmentGroup->getMongoFragment());
        $this->elasticEntityManager->merge($fragmentGroup->getElasticFragment());
    }

    public function delete(NodeElementInterface|RelationElementInterface $element): void
    {
        $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
        $this->cypherEntityManager->delete($fragmentGroup->getCypherFragment());
        $this->mongoEntityManager->delete($fragmentGroup->getMongoFragment());
        $this->elasticEntityManager->delete($fragmentGroup->getElasticFragment());
    }

    public function flush(): void
    {
        $this->cypherEntityManager->flush();
        $this->mongoEntityManager->flush();
        $this->elasticEntityManager->flush();
    }

    public function getElement(UuidInterface $uuid): null|NodeElementInterface|RelationElementInterface
    {
        $node = $this->getNode($uuid);
        if ($node) {
            return $node;
        }
        $relation = $this->getRelation($uuid);
        if ($relation) {
            return $relation;
        }

        return null;
    }

    public function getNode(UuidInterface $uuid): ?NodeElementInterface
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (node {id: $id}) RETURN node',
                [
                    'id' => $uuid->toString(),
                ]
            )
        );
        try {
            $cypherFragment = $this->neo4jClientHelper->getNodeFromLaudisNode($res->first()->get('node'));
            if (!$cypherFragment) {
                return null;
            }
        } catch (\OutOfBoundsException $e) {
            return null;
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($cypherFragment->getLabels()[0], $uuid->toString());

        return $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment);
    }

    public function getRelation(UuidInterface $uuid): ?RelationElementInterface
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (startNode)-[relation {id: $id}]->(endNode) RETURN startNode, relation, endNode',
                [
                    'id' => $uuid->toString(),
                ]
            )
        );
        try {
            $cypherFragment = $this->neo4jClientHelper->getRelationFromLaudisRelation(
                $res->first()->get('relation'),
                $res->first()->get('startNode'),
                $res->first()->get('endNode')
            );
            if (!$cypherFragment) {
                return null;
            }
        } catch (\OutOfBoundsException $e) {
            return null;
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($cypherFragment->getType(), $uuid->toString());

        return $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment);
    }
}
