<?php

declare(strict_types=1);

namespace App\Service\Index;

use App\Helper\CypherHelper;
use App\Service\GraphStructureService;
use App\Service\MaintenanceLockService;
use App\Type\MaintenanceLockType;
use Laudis\Neo4j\Databags\Statement;
use Psr\Log\LoggerInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Makes sure that every node label and every relationship type has a range index on `id`, so that typed element
 * queries (`MATCH (e:`Type` {id: $id})`) are index seeks. Only creates, never drops: types can come back.
 *
 * MongoDB and Elasticsearch need nothing: the element id is the document identifier (`_id`) there.
 *
 * The index is a plain range index, not a uniqueness constraint. Lookups are equally fast (a constraint is backed by
 * the same kind of index), but a constraint adds a uniqueness check to every write and can't be created while
 * duplicates exist; ids are unique by construction and by the check in the create path.
 */
class IndexEnsurer
{
    public const int DEFAULT_AWAIT_SECONDS = 600;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private GraphStructureService $graphStructureService,
        private IndexNameService $indexNameService,
        private MaintenanceLockService $maintenanceLockService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Claims `lock:maintenance:database-indices` (unless the caller already holds it) and creates the indices.
     *
     * @return IndexEnsureResult|null null if the work was skipped: the lock is held by somebody else, or a backup load /
     *                                database reset is running ({@see MaintenanceLockType::DATABASE}); $skipReason says why
     */
    public function ensureWithLock(
        bool $nodes = true,
        bool $relations = true,
        ?int $awaitSeconds = null,
        bool $dryRun = false,
        bool $lockHeldByCaller = false,
        ?string &$skipReason = null,
    ): ?IndexEnsureResult {
        $lock = null;
        if (!$lockHeldByCaller) {
            $lock = $this->maintenanceLockService->claim(MaintenanceLockType::DATABASE_INDICES);
            if (null === $lock) {
                $skipReason = 'another process holds lock:maintenance:database-indices, it is creating the indices already';

                return null;
            }
            if ($this->maintenanceLockService->isHeld(MaintenanceLockType::DATABASE)) {
                $this->maintenanceLockService->release($lock);
                $skipReason = 'skipped: backup load or reset in progress';

                return null;
            }
        }
        try {
            return $this->ensure($nodes, $relations, $awaitSeconds, $dryRun);
        } finally {
            if (null !== $lock) {
                $this->maintenanceLockService->release($lock);
            }
        }
    }

    /**
     * Creates the missing indices; does not touch locks. The types come from the database, unless the caller knows
     * them better ($readStructure false uses the structure which is set in the graph structure service, e.g. from a
     * backup which is about to be loaded into an empty database).
     */
    public function ensure(bool $nodes = true, bool $relations = true, ?int $awaitSeconds = null, bool $dryRun = false, bool $readStructure = true): IndexEnsureResult
    {
        if ($readStructure) {
            $this->graphStructureService->loadGraphStructure();
        }
        $existing = $this->getExistingIdIndices();
        $created = [];
        $alreadyThere = [];
        $equivalent = [];

        $work = [];
        if ($nodes) {
            foreach ($this->graphStructureService->getNodeTypes() as $label) {
                $work[] = ['node', $label, $this->indexNameService->getNodeIndexName($label)];
            }
        }
        if ($relations) {
            foreach ($this->graphStructureService->getRelationTypes() as $type) {
                $work[] = ['relation', $type, $this->indexNameService->getRelationIndexName($type)];
            }
        }

        foreach ($work as [$kind, $type, $name]) {
            $existingName = $existing[$kind][$type] ?? null;
            if (null !== $existingName) {
                if ($existingName === $name) {
                    $alreadyThere[] = $name;
                } else {
                    $equivalent[] = sprintf('%s %s (index %s)', $kind, $type, $existingName);
                }
                continue;
            }
            if (!$dryRun) {
                $this->createIndex($kind, $type, $name);
            }
            $created[] = $name;
        }

        if (!$dryRun && null !== $awaitSeconds && [] !== $created) {
            $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                'CALL db.awaitIndexes($seconds)',
                ['seconds' => $awaitSeconds]
            ));
        }

        return new IndexEnsureResult($created, $alreadyThere, $equivalent);
    }

    /**
     * Drops every index which Ember Nexus manages (used when the database is reset; dropping nodes does not drop
     * their indices).
     *
     * @return string[] names of the dropped indices
     */
    public function dropManagedIndices(): array
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'SHOW INDEXES YIELD name WHERE name STARTS WITH $prefix RETURN name',
            ['prefix' => IndexNameService::PREFIX]
        ));
        $dropped = [];
        foreach ($result as $row) {
            $name = $row->get('name');
            if (!is_string($name)) {
                continue;
            }
            $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                sprintf('DROP INDEX %s IF EXISTS', CypherHelper::escapeIdentifier($name))
            ));
            $dropped[] = $name;
        }

        return $dropped;
    }

    private function createIndex(string $kind, string $type, string $name): void
    {
        $escapedName = CypherHelper::escapeIdentifier($name);
        $escapedType = CypherHelper::escapeIdentifier($type);
        $statement = 'node' === $kind
            ? sprintf('CREATE RANGE INDEX %s IF NOT EXISTS FOR (n:%s) ON (n.id)', $escapedName, $escapedType)
            : sprintf('CREATE RANGE INDEX %s IF NOT EXISTS FOR ()-[r:%s]-() ON (r.id)', $escapedName, $escapedType);
        $this->logger->info(sprintf("Creating index '%s' for %s type '%s'.", $name, $kind, $type));
        $this->cypherEntityManager->getClient()->runStatement(Statement::create($statement));
    }

    /**
     * Range indices on exactly `id`, grouped by kind and type: the name of the index which covers it. A uniqueness
     * constraint also creates such an index, so those count as equivalent.
     *
     * @return array{node: array<string, string>, relation: array<string, string>}
     */
    private function getExistingIdIndices(): array
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "SHOW INDEXES YIELD name, type, entityType, labelsOrTypes, properties\n".
            "WHERE type = 'RANGE' AND properties = ['id'] AND size(labelsOrTypes) = 1\n".
            'RETURN name, entityType, labelsOrTypes[0] AS typeName'
        ));
        $existing = ['node' => [], 'relation' => []];
        foreach ($result as $row) {
            $name = $row->get('name');
            $typeName = $row->get('typeName');
            if (!is_string($name) || !is_string($typeName)) {
                continue;
            }
            $kind = 'NODE' === $row->get('entityType') ? 'node' : 'relation';
            $existing[$kind][$typeName] = $existing[$kind][$typeName] ?? $name;
            if ($this->indexNameService->isManagedIndexName($name)) {
                // prefer reporting the managed index if there are several
                $existing[$kind][$typeName] = $name;
            }
        }

        return $existing;
    }
}
