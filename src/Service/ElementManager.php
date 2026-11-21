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
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\CypherHelper;
use App\Helper\Neo4jClientHelper;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Type\ElementKind;
use App\Type\FragmentGroup;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Relationship;
use MongoDB\Model\BSONDocument;
use OutOfBoundsException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoDataStructures\Type\Document;
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
        private LoggerInterface $logger,
        private ElementTypeService $elementTypeService,
        private ElementTypeOverlayService $elementTypeOverlayService,
        private GraphStructureService $graphStructureService,
        private PendingSearchDocumentService $pendingSearchDocumentService,
        private AccessResultCacheService $accessResultCacheService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
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
        $this->accessResultCacheService->forget();
        foreach ($this->createQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreCreateEvent($element));
            $this->registerElementType($element);
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->create($fragmentGroup->getCypherFragment());
            $this->mongoEntityManager->create($fragmentGroup->getMongoFragment());
            // written after the post create events, which add the search access to the document
            $elasticDocument = $fragmentGroup->getElasticFragment();
            $this->pendingSearchDocumentService->hold($element->getId()?->toString() ?? '', $elasticDocument);
        }

        foreach ($this->mergeQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreMergeEvent($element));
            // merging creates the element if it does not exist yet; the type is registered in any case, as the lookup
            // table answers with a (wrong) candidate for ids it does not know and can't be asked whether it knows one
            $this->registerElementType($element);
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->merge($fragmentGroup->getCypherFragment());
            $this->logIfMongoDocumentMissing($element, $fragmentGroup, 'updated');
            $this->logIfElasticDocumentMissing($element, $fragmentGroup, 'updated');
            // merging into Mongo/Elasticsearch upserts, so a missing document is created rather than left absent
            $this->mongoEntityManager->merge($fragmentGroup->getMongoFragment());
            $this->elasticEntityManager->merge($fragmentGroup->getElasticFragment());
        }

        foreach ($this->deleteQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPreDeleteEvent($element));
            $fragmentGroup = $this->elementFragmentizeService->fragmentize($element);
            $this->cypherEntityManager->delete($fragmentGroup->getCypherFragment());
            $this->logIfMongoDocumentMissing($element, $fragmentGroup, 'deleted');
            // deleting a missing Mongo document is a no-op, but the Elasticsearch client throws on a missing one, so
            // it is only called when the document actually exists
            $this->mongoEntityManager->delete($fragmentGroup->getMongoFragment());
            if (!$this->logIfElasticDocumentMissing($element, $fragmentGroup, 'deleted')) {
                $this->elasticEntityManager->delete($fragmentGroup->getElasticFragment());
            }
        }

        $this->cypherEntityManager->flush();
        $this->mongoEntityManager->flush();
        $this->elasticEntityManager->flush();
        $this->accessResultCacheService->forget();
        // the elements are written, so a new type is part of the structure a reload reads from now on
        $this->graphStructureService->applyPendingInvalidation();

        foreach ($this->createQueue as $element) {
            $this->eventDispatcher->dispatch(new ElementPostCreateEvent($element));
        }
        foreach ($this->pendingSearchDocumentService->releaseAll() as $elasticDocument) {
            $this->elasticEntityManager->create($elasticDocument);
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
        $this->accessResultCacheService->forget();

        foreach ([...$this->createQueue, ...$this->deleteQueue] as $element) {
            $id = $element->getId();
            if (null !== $id) {
                $this->elementTypeService->forget($id);
            }
        }

        $this->createQueue = [];
        $this->mergeQueue = [];
        $this->deleteQueue = [];

        return $this;
    }

    private function getTypeOfElement(NodeElementInterface|RelationElementInterface $element): ?string
    {
        return $element instanceof NodeElementInterface ? $element->getLabel() : $element->getType();
    }

    /**
     * Remembers the type of a new element in the Redis overlay of the id -> type lookup. This has to happen before the
     * database write: from then on the element is resolvable even though it is not part of the loaded lookup table.
     */
    private function registerElementType(NodeElementInterface|RelationElementInterface $element): void
    {
        $id = $element->getId();
        $type = $this->getTypeOfElement($element);
        if (null === $id || null === $type) {
            return;
        }
        $kind = $element instanceof NodeElementInterface ? ElementKind::NODE : ElementKind::RELATION;
        $this->elementTypeOverlayService->register($id->toString(), (new ResolvedType($kind, $type))->encode());
        $this->graphStructureService->noteType(ElementKind::NODE === $kind, $type);
        $this->elementTypeService->forget($id);
    }

    private function requireString(mixed $value, string $name): string
    {
        if (!is_string($value)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected %s to be string, not %s.', $name, get_debug_type($value))); // @codeCoverageIgnore
        }

        return $value;
    }

    /**
     * Returns true if the element's Mongo document did not exist (and logged a notice for it).
     */
    private function logIfMongoDocumentMissing(NodeElementInterface|RelationElementInterface $element, FragmentGroup $fragmentGroup, string $verb): bool
    {
        $mongoFragment = $fragmentGroup->getMongoFragment();
        $collection = $this->requireString($mongoFragment->getCollection(), 'mongo collection');
        $identifier = $this->requireString($mongoFragment->getIdentifier(), 'mongo document identifier');
        if (null !== $this->mongoEntityManager->getOneByIdentifier($collection, $identifier)) {
            return false;
        }
        $this->logger->notice(sprintf(
            "To be %s element with id '%s' does not have a MongoDB document.",
            $verb,
            $element->getId()?->toString() ?? 'null'
        ));

        return true;
    }

    /**
     * Returns true if the element's Elasticsearch document did not exist (and logged a notice for it). On delete,
     * callers must skip the actual delete call themselves when this returns true.
     */
    private function logIfElasticDocumentMissing(NodeElementInterface|RelationElementInterface $element, FragmentGroup $fragmentGroup, string $verb): bool
    {
        $elasticFragment = $fragmentGroup->getElasticFragment();
        $index = $this->requireString($elasticFragment->getIndex(), 'elasticsearch index');
        $identifier = $this->requireString($elasticFragment->getIdentifier(), 'elasticsearch document identifier');
        if (null !== $this->elasticEntityManager->getOneByIdentifier($index, $identifier)) {
            return false;
        }
        $this->logger->notice(sprintf(
            "To be %s element with id '%s' does not have an Elasticsearch document.",
            $verb,
            $element->getId()?->toString() ?? 'null'
        ));

        return true;
    }

    /**
     * Loads many elements with one Neo4j query and one MongoDB query per type instead of one of each per element.
     * Elements which don't exist are left out, the order of $ids is kept.
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     *
     * @param Uuid[] $ids
     *
     * @return list<NodeElementInterface|RelationElementInterface>
     */
    public function getElements(array $ids): array
    {
        $nodeIdsByLabel = [];
        $relationIdsByType = [];
        foreach ($this->elementTypeService->resolveMany($ids) as $rawId => $resolved) {
            if (null === $resolved) {
                continue;
            }
            if ($resolved->isNode()) {
                $nodeIdsByLabel[$resolved->getType()][] = $rawId;
            } else {
                $relationIdsByType[$resolved->getType()][] = $rawId;
            }
        }

        /** @var array<string, NodeElementInterface|RelationElementInterface> $elementsById */
        $elementsById = [];
        foreach ($nodeIdsByLabel as $label => $rawIds) {
            $documents = $this->getMongoDocuments($label, $rawIds);
            $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                sprintf('UNWIND $ids AS id MATCH (node:%s {id: id}) RETURN node', CypherHelper::escapeIdentifier($label)),
                ['ids' => $rawIds]
            ));
            foreach ($res as $row) {
                $rawNode = $row->get('node');
                if (!($rawNode instanceof Node)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property node as Node, not %s.', get_debug_type($rawNode))); // @codeCoverageIgnore
                }
                $rawId = $this->requireString($rawNode->getProperty('id'), 'node id');
                $node = $this->elementDefragmentizeService->defragmentize($this->neo4jClientHelper->getNodeFromLaudisNode($rawNode), $documents[$rawId] ?? null);
                if ($node instanceof NodeElementInterface) {
                    $elementsById[$rawId] = $node;
                }
            }
        }
        foreach ($relationIdsByType as $type => $rawIds) {
            $documents = $this->getMongoDocuments($type, $rawIds);
            $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                sprintf('UNWIND $ids AS id MATCH (startNode)-[relation:%s {id: id}]->(endNode) RETURN startNode, relation, endNode', CypherHelper::escapeIdentifier($type)),
                ['ids' => $rawIds]
            ));
            foreach ($res as $row) {
                $rawRelation = $row->get('relation');
                $rawStartNode = $row->get('startNode');
                $rawEndNode = $row->get('endNode');
                if (!($rawRelation instanceof Relationship) || !($rawStartNode instanceof Node) || !($rawEndNode instanceof Node)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected cypher response to return properties relation as Relationship, startNode and endNode as Node.'); // @codeCoverageIgnore
                }
                $rawId = $this->requireString($rawRelation->getProperty('id'), 'relation id');
                $relation = $this->elementDefragmentizeService->defragmentize(
                    $this->neo4jClientHelper->getRelationFromLaudisRelation($rawRelation, $rawStartNode, $rawEndNode),
                    $documents[$rawId] ?? null
                );
                if ($relation instanceof RelationElementInterface) {
                    $elementsById[$rawId] = $relation;
                }
            }
        }

        $elements = [];
        foreach ($ids as $id) {
            if (isset($elementsById[$id->toString()])) {
                $elements[] = $elementsById[$id->toString()];
            }
        }

        return $elements;
    }

    /**
     * @param list<string> $rawIds
     *
     * @return array<string, DocumentInterface> by identifier
     */
    private function getMongoDocuments(string $collection, array $rawIds): array
    {
        $cursor = $this->mongoEntityManager->getClient()
            ->selectDatabase((string) $this->mongoEntityManager->getDatabase())
            ->selectCollection($collection)
            ->find(['_id' => ['$in' => $rawIds]]);
        $documents = [];
        foreach ($cursor as $result) {
            if (!($result instanceof BSONDocument)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected mongo to return BSON documents, not %s.', get_debug_type($result))); // @codeCoverageIgnore
            }
            /** @var array<string, mixed> $properties */
            $properties = $result->getArrayCopy();
            unset($properties['_id']);
            $identifier = $this->requireString($result->offsetGet('_id'), 'mongo document identifier');
            $documents[$identifier] = (new Document())
                ->addProperties($properties)
                ->setIdentifier($identifier)
                ->setCollection($collection);
        }

        return $documents;
    }

    public function getElement(Uuid $id): NodeElementInterface|RelationElementInterface|null
    {
        $resolved = $this->elementTypeService->resolve($id);
        if (null === $resolved) {
            return null;
        }

        return $resolved->isNode() ? $this->getNode($id) : $this->getRelation($id);
    }

    public function elementExists(Uuid $id): bool
    {
        return $this->elementTypeService->elementExists($id);
    }

    public function getElementOrFail(Uuid $id): NodeElementInterface|RelationElementInterface
    {
        $element = $this->getElement($id);
        if (null === $element) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        return $element;
    }

    public function getNode(Uuid $id): ?NodeElementInterface
    {
        $label = $this->elementTypeService->resolveNodeLabel($id);
        if (null === $label) {
            return null;
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf('MATCH (node:%s {id: $id}) RETURN node', CypherHelper::escapeIdentifier($label)),
                [
                    'id' => $id->toString(),
                ]
            )
        );
        try {
            $rawNode = $res->first()->get('node');
            if (!($rawNode instanceof Node)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property node as Node, not %s.', get_debug_type($rawNode))); // @codeCoverageIgnore
            }
            $cypherFragment = $this->neo4jClientHelper->getNodeFromLaudisNode($rawNode);
        } catch (OutOfBoundsException $e) {
            return null;
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($cypherFragment->getLabels()[0], $id->toString());

        $node = $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment);
        if (!($node instanceof NodeElementInterface)) {
            return null;
        }

        return $node;
    }

    public function getRelation(Uuid $id): ?RelationElementInterface
    {
        $relationType = $this->elementTypeService->resolveRelationType($id);
        if (null === $relationType) {
            return null;
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf('MATCH (startNode)-[relation:%s {id: $id}]->(endNode) RETURN startNode, relation, endNode', CypherHelper::escapeIdentifier($relationType)),
                [
                    'id' => $id->toString(),
                ]
            )
        );
        try {
            $rawRelation = $res->first()->get('relation');
            if (!($rawRelation instanceof Relationship)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property relation as Relationship, not %s.', get_debug_type($rawRelation))); // @codeCoverageIgnore
            }
            $rawStartNode = $res->first()->get('startNode');
            if (!($rawStartNode instanceof Node)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property startNode as Node, not %s.', get_debug_type($rawStartNode))); // @codeCoverageIgnore
            }
            $rawEndNode = $res->first()->get('endNode');
            if (!($rawEndNode instanceof Node)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property endNode as Node, not %s.', get_debug_type($rawEndNode))); // @codeCoverageIgnore
            }
            $cypherFragment = $this->neo4jClientHelper->getRelationFromLaudisRelation(
                $rawRelation,
                $rawStartNode,
                $rawEndNode
            );
        } catch (OutOfBoundsException $e) {
            return null;
        }
        $type = $cypherFragment->getType();
        if (null === $type) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to get relationship type.');
        }
        $documentFragment = $this->mongoEntityManager->getOneByIdentifier($type, $id->toString());

        $relation = $this->elementDefragmentizeService->defragmentize($cypherFragment, $documentFragment);
        if (!($relation instanceof RelationElementInterface)) {
            return null;
        }

        return $relation;
    }
}
