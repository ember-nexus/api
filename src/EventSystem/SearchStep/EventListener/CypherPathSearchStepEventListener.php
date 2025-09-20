<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\EventListener;

use App\Antlr\CypherPathSubsetGrammar;
use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\SearchStepType;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Path;
use Laudis\Neo4j\Types\UnboundRelationship;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class CypherPathSearchStepEventListener
{
    public const SearchStepType TYPE = SearchStepType::CYPHER_PATH_SUBSET;

    public function __construct(
        private CypherPathSubsetGrammar $cypherPathSubsetGrammar,
        private CypherEntityManager $cypherEntityManager,
        private AccessChecker $accessChecker,
        private AuthProvider $authProvider,
        private Stopwatch $stopwatch,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    /**
     * @return array{nodeIds: UuidInterface[], relationIds: UuidInterface[]}
     */
    private function parseRawPathFromRow(CypherMap $row): array
    {
        $rawPath = $row->get('path');
        /** @var Path $rawPath */
        $nodeIds = [];
        $relationIds = [];
        foreach ($rawPath->getNodes() as $rawNode) {
            /**
             * @phpstan-ignore-next-line greater.alwaysTrue
             */
            if (!($rawNode instanceof Node)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.nodes as Node, not %s.', get_debug_type($rawNode))); // @codeCoverageIgnore
            }
            $rawId = $rawNode->getProperty('id');
            if (!is_string($rawId)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.node.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
            }
            $nodeIds[] = UuidV4::fromString($rawId);
        }
        foreach ($rawPath->getRelationships() as $rawRelation) {
            /**
             * @phpstan-ignore-next-line greater.alwaysTrue
             */
            if (!($rawRelation instanceof UnboundRelationship)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property path.relationships as UnboundRelationship, not %s.', get_debug_type($rawRelation))); // @codeCoverageIgnore
            }
            $relationIds[] = UuidV4::fromString($rawRelation->getProperty('id'));
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
     * @param array<array{nodeIds: UuidInterface[], relationIds: UuidInterface[]}> $paths
     *
     * @return UuidInterface[]
     */
    private function getElementIdsFromPaths(array $paths): array
    {
        $allElementIds = [
            ...array_merge(...array_column($paths, 'nodeIds')),
            ...array_merge(...array_column($paths, 'relationIds')),
        ];

        return array_values(array_unique($allElementIds));
    }

    /**
     * @param array<array{nodeIds: UuidInterface[], relationIds: UuidInterface[]}> $paths
     *
     * @return array<array{nodeIds: UuidInterface[], relationIds: UuidInterface[]}>
     */
    private function filterPathsToAccessibleOnly(SearchStepEvent $event, array $paths): array
    {
        $allElementIds = $this->getElementIdsFromPaths($paths);

        $this->stopwatch->start(sprintf('%s:checkUserAccessToMultipleElements', self::TYPE->value));
        $filteredElementIds = $this->accessChecker->checkUserAccessToMultipleElements($this->authProvider->getUserId(), $allElementIds);
        $this->stopwatch->stop(sprintf('%s:checkUserAccessToMultipleElements', self::TYPE->value));

        $filteredPaths = [];
        foreach ($paths as $path) {
            if (!empty(array_diff($path['nodeIds'], $filteredElementIds))) {
                // path contains at least one node which the user does not have access to -> do not add to filtered paths
                continue;
            }
            if (!empty(array_diff($path['relationIds'], $filteredElementIds))) {
                // path contains at least one relation which the user does not have access to -> do not add to filtered paths
                continue;
            }
            $filteredPaths[] = $path;
        }

        if (count($filteredPaths) !== count($paths)) {
            $event->addDebugData(
                sprintf('%s:accessiblePaths', self::class),
                [
                    'message' => 'Removed paths due to missing access rights.',
                ]
            );
        }

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

    public function onSearchStepEvent(SearchStepEvent $event): void
    {
        if (self::TYPE !== $event->getType()) {
            return;
        }

        $event->addDebugData('step-handler', sprintf('%s (%s)', self::TYPE->value, self::class));

        $start = microtime(true);
        $event->addDebugData('start', $start);

        $query = $this->getQueryFromEvent($event);
        $this->cypherPathSubsetGrammar->validateQuery($query);

        $this->stopwatch->start(sprintf('%s:executeUserQuery:transaction', self::TYPE->value));
        $cypherClient = $this->cypherEntityManager->getClient();
        $parameters = $event->getParameters();
        $result = $cypherClient->readTransaction(static function (TransactionInterface $tsx) use ($query, $parameters): SummarizedResult {
            return $tsx->run($query, $parameters);
        });
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
