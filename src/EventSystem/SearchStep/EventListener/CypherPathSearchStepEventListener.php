<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\EventListener;

use App\Antlr\CypherPathSubsetGrammar;
use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\SearchStepType;
use App\Type\Uuid;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Databags\TransactionConfiguration;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Path;
use Laudis\Neo4j\Types\UnboundRelationship;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Stopwatch\Stopwatch;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class CypherPathSearchStepEventListener
{
    public const SearchStepType TYPE = SearchStepType::CYPHER_PATH_SUBSET;

    /**
     * Neo4j aborts user queries which run longer, as everybody (including anonymous users) may search and the grammar
     * allows expensive patterns like unbounded traversals.
     */
    public const float QUERY_TIMEOUT_IN_SECONDS = 30.0;

    public function __construct(
        private CypherPathSubsetGrammar $cypherPathSubsetGrammar,
        private CypherEntityManager $cypherEntityManager,
        private AccessChecker $accessChecker,
        private AuthProvider $authProvider,
        private Stopwatch $stopwatch,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * @return array{nodeIds: Uuid[], relationIds: Uuid[]}
     */
    private function parseRawPathFromRow(CypherMap $row): array
    {
        if (!$row->hasKey('path')) {
            return $this->parseIdsFromRow($row);
        }
        /** @var Path $rawPath */
        $rawPath = $row->get('path');
        $nodeIds = [];
        $relationIds = [];
        foreach ($rawPath->getNodes() as $rawNode) {
            /**
             * @psalm-suppress DocblockTypeContradiction
             */
            if (!($rawNode instanceof Node)) { // @phpstan-ignore instanceof.alwaysTrue
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.nodes as Node, not %s.', get_debug_type($rawNode))); // @codeCoverageIgnore
            }
            $rawId = $rawNode->getProperty('id');
            if (!is_string($rawId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.node.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
            }
            $nodeIds[] = Uuid::fromString($rawId);
        }
        foreach ($rawPath->getRelationships() as $rawRelation) {
            /**
             * @psalm-suppress DocblockTypeContradiction
             */
            if (!($rawRelation instanceof UnboundRelationship)) { // @phpstan-ignore instanceof.alwaysTrue
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.relationships as UnboundRelationship, not %s.', get_debug_type($rawRelation))); // @codeCoverageIgnore
            }
            $rawId = $rawRelation->getProperty('id');
            if (!is_string($rawId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.relationships.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
            }
            $relationIds[] = Uuid::fromString($rawId);
        }
        $nodeCount = count($nodeIds);
        $relationCount = count($relationIds);
        if ($nodeCount != $relationCount + 1) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Result contains path with unexpected number of relations. For %d number of nodes expected %d number of relations, got %d.', $nodeCount, $nodeCount - 1, $relationCount));
        }

        return [
            'nodeIds' => $nodeIds,
            'relationIds' => $relationIds,
        ];
    }

    /**
     * Row of a rewritten query, see {@see CypherPathSubsetGrammar::prepareQuery()}.
     *
     * @return array{nodeIds: Uuid[], relationIds: Uuid[]}
     */
    private function parseIdsFromRow(CypherMap $row): array
    {
        $ids = [];
        foreach (['nodeIds', 'relationIds'] as $column) {
            $ids[$column] = [];
            /** @var iterable<mixed> $rawIds */
            $rawIds = $row->get($column);
            /** @psalm-suppress MixedAssignment */
            foreach ($rawIds as $rawId) {
                if (!is_string($rawId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.%s as string, not %s.', $column, get_debug_type($rawId))); // @codeCoverageIgnore
                }
                $ids[$column][] = Uuid::fromString($rawId);
            }
        }
        $nodeCount = count($ids['nodeIds']);
        $relationCount = count($ids['relationIds']);
        if ($nodeCount != $relationCount + 1) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Result contains path with unexpected number of relations. For %d number of nodes expected %d number of relations, got %d.', $nodeCount, $nodeCount - 1, $relationCount));
        }

        return $ids;
    }

    /**
     * @param array<array{nodeIds: Uuid[], relationIds: Uuid[]}> $paths
     *
     * @return Uuid[]
     */
    private function getElementIdsFromPaths(array $paths): array
    {
        $unique = [];
        foreach (['nodeIds', 'relationIds'] as $column) {
            foreach ($paths as $path) {
                foreach ($path[$column] as $elementId) {
                    $unique[$elementId->toString()] ??= $elementId;
                }
            }
        }

        return array_values($unique);
    }

    /**
     * @param array<array{nodeIds: Uuid[], relationIds: Uuid[]}> $paths
     *
     * @return array<array{nodeIds: Uuid[], relationIds: Uuid[]}>
     */
    private function filterPathsToAccessibleOnly(SearchStepEvent $event, array $paths): array
    {
        $allElementIds = $this->getElementIdsFromPaths($paths);

        $this->stopwatch->start(sprintf('%s:checkUserAccessToMultipleElements', self::TYPE->value));
        $filteredElementIds = $this->accessChecker->checkUserAccessToMultipleElements($this->authProvider->getUserId(), $allElementIds);
        $this->stopwatch->stop(sprintf('%s:checkUserAccessToMultipleElements', self::TYPE->value));

        $accessible = [];
        foreach ($filteredElementIds as $filteredElementId) {
            $accessible[$filteredElementId->toString()] = true;
        }

        $filteredPaths = [];
        foreach ($paths as $path) {
            // a path is only returned if the user has access to all of its nodes and relations
            foreach ([...$path['nodeIds'], ...$path['relationIds']] as $elementId) {
                if (!isset($accessible[$elementId->toString()])) {
                    continue 2;
                }
            }
            $filteredPaths[] = $path;
        }

        // deliberately no debug information about removed paths: whether something was removed would tell the caller
        // that an inaccessible element matched the query

        return $filteredPaths;
    }

    private function getQueryFromEvent(SearchStepEvent $event): string
    {
        $query = $event->getQuery();
        if (!is_string($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'string', $query);
        }

        return $query;
    }

    #[AsEventListener]
    public function onSearchStepEvent(SearchStepEvent $event): void
    {
        if (self::TYPE !== $event->getType()) {
            return;
        }

        $event->addDebugData('step-handler', sprintf('%s (%s)', self::TYPE->value, self::class));

        $start = microtime(true);
        $event->addDebugData('start', $start);

        $query = $this->getQueryFromEvent($event);
        $query = $this->cypherPathSubsetGrammar->prepareQuery($query);

        $this->stopwatch->start(sprintf('%s:executeUserQuery:transaction', self::TYPE->value));
        $cypherClient = $this->cypherEntityManager->getClient();
        $parameters = $event->getParameters();
        $result = $cypherClient->readTransaction(static function (TransactionInterface $tsx) use ($query, $parameters): SummarizedResult {
            return $tsx->run($query, $parameters);
        }, null, TransactionConfiguration::create(self::QUERY_TIMEOUT_IN_SECONDS));
        $this->stopwatch->stop(sprintf('%s:executeUserQuery:transaction', self::TYPE->value));

        $paths = [];
        foreach ($result->toArray() as $row) {
            $paths[] = $this->parseRawPathFromRow($row);
        }

        $filteredPaths = $this->filterPathsToAccessibleOnly($event, $paths);

        $event->setResults([
            'paths' => $filteredPaths,
        ]);

        $end = microtime(true);
        $event->addDebugData('end', $end);
        $event->addDebugData('duration', $end - $start);
        $event->stopPropagation();
    }
}
