<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPreDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPreMergeEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Helper\Neo4jClientHelper;
use Laudis\Neo4j\Databags\Statement;
use OutOfBoundsException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

class ElementManager
{
    /**
     * @var array<NodeElementInterface|RelationElementInterface>
     */
    private array $createQueue = [];
    /**
     * @var array<NodeElementInterface|RelationElementInterface>
     */
    private array $mergeQueue = [];
    /**
     * @var array<NodeElementInterface|RelationElementInterface>
     */
    private array $deleteQueue = [];

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private ElementFragmentizeService $elementFragmentizeService,
        private ElementDefragmentizeService $elementDefragmentizeService,
        private Neo4jClientHelper $neo4jClientHelper,
        private EventDispatcherInterface $eventDispatcher,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function create(NodeElementInterface|RelationElementInterface $element): static
    {
        $this->createQueue[] = $element;

        return $this;
    }

    public function merge(NodeElementInterface|RelationElementInterface $element): static
    {
        $this->mergeQueue[] = $element;

        return $this;
    }

    public function delete(NodeElementInterface|RelationElementInterface $element): static
    {
        $this->deleteQueue[] = $element;

        return $this;
    }

    public function flush(): static
    {
        foreach ($this->createQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreCreateEvent($element));
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->create($fragmentGroup->getCypherFragment());
            $this->mongoEntityManager->create($fragmentGroup->getMongoFragment());
            $this->elasticEntityManager->create($fragmentGroup->getElasticFragment());
        }

        foreach ($this->mergeQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreMergeEvent($element));
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->merge($fragmentGroup->getCypherFragment());
            $this->mongoEntityManager->merge($fragmentGroup->getMongoFragment());
            $this->elasticEntityManager->merge($fragmentGroup->getElasticFragment());
        }

        foreach ($this->deleteQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreDeleteEvent($element));
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->delete($fragmentGroup->getCypherFragment());
            $this->mongoEntityManager->delete($fragmentGroup->getMongoFragment());
            $this->elasticEntityManager->delete($fragmentGroup->getElasticFragment());
        }

        $this->cypherEntityManager->flush();
        $this->mongoEntityManager->flush();
        $this->elasticEntityManager->flush();

        foreach ($this->createQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPostCreateEvent($element));
        }

        foreach ($this->mergeQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPostMergeEvent($element));
        }

        foreach ($this->deleteQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPostDeleteEvent($element));
        }

        $this->cypherEntityManager->flush();
        $this->mongoEntityManager->flush();
        $this->elasticEntityManager->flush();

        $this->createQueue = [];
        $this->mergeQueue = [];
        $this->deleteQueue = [];

        return $this;
    }

    public function getElement(UuidInterface $id): NodeElementInterface|RelationElementInterface|null
    {
        $node = $this->getNode($id);
        if ($node) {
            return $node;
        }
        $relation = $this->getRelation($id);
        if ($relation) {
            return $relation;
        }

        return null;
    }

    public function getNode(UuidInterface $id): ?NodeElementInterface
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (node {id: $id}) RETURN node',
                [
                    'id' => $id->toString(),
                ]
            )
        );
        try {
            $cypherFragment = $this->neo4jClientHelper->getNodeFromLaudisNode($res->first()->get('node'));
        } catch (OutOfBoundsException $e) {
            return null;
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($cypherFragment->getLabels()[0], $id->toString());
        $fileFragment = null;

        $node = $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment, $fileFragment);
        if (!($node instanceof NodeElementInterface)) {
            return null;
        }

        return $node;
    }

    public function getRelation(UuidInterface $id): ?RelationElementInterface
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (startNode)-[relation {id: $id}]->(endNode) RETURN startNode, relation, endNode',
                [
                    'id' => $id->toString(),
                ]
            )
        );
        try {
            $cypherFragment = $this->neo4jClientHelper->getRelationFromLaudisRelation(
                $res->first()->get('relation'),
                $res->first()->get('startNode'),
                $res->first()->get('endNode')
            );
        } catch (OutOfBoundsException $e) {
            return null;
        }
        $type = $cypherFragment->getType();
        if (null === $type) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to get relationship type.');
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($type, $id->toString());
        $fileFragment = null;

        $relation = $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment, $fileFragment);
        if (!($relation instanceof RelationElementInterface)) {
            return null;
        }

        return $relation;
    }
}
