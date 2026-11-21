<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
use Exception;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Predis\Client as RedisClient;
use Symfony\Contracts\Service\ResetInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

/**
 * Node labels and relation types of the database. Read from Neo4j at most once per hour: the structure is cached in
 * Redis for all requests, and in the service for the rest of the request. Creating an element with an unknown type
 * drops the cache, see {@see noteType()}, and a lookup which fails with a cached structure reloads it once.
 */
class GraphStructureService implements ResetInterface
{
    public const int REDIS_TTL_IN_SECONDS = 3600;

    private bool $loaded = false;

    /**
     * True if the structure was read from the database by this process, so that it can't be stale.
     */
    private bool $fresh = false;

    private bool $invalidationPending = false;

    /**
     * @var string[]
     */
    private array $nodeTypes = [];

    /**
     * @var string[]
     */
    private array $relationTypes = [];

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
    ) {
    }

    public function reset(): void
    {
        $this->nodeTypes = [];
        $this->relationTypes = [];
        $this->loaded = false;
        $this->fresh = false;
        $this->invalidationPending = false;
    }

    /**
     * Uses a structure which is known from elsewhere, e.g. from the summary of a backup which is about to be loaded
     * into an empty database, and caches it like a structure read from the database.
     *
     * @param string[] $nodeTypes
     * @param string[] $relationTypes
     */
    public function setStructure(array $nodeTypes, array $relationTypes): void
    {
        $this->nodeTypes = array_values($nodeTypes);
        $this->relationTypes = array_values($relationTypes);
        $this->loaded = true;
        $this->fresh = true;
        $this->invalidationPending = false;
        $this->writeToRedis();
    }

    /**
     * Drops the cached structure and reads it from the database again.
     */
    public function refresh(): void
    {
        $this->invalidationPending = false;
        try {
            $this->redisClient->del([(string) $this->redisKeyFactory->getGraphStructureRedisKey()]);
        } catch (Throwable) {
            // without Redis nothing is cached across requests either
        }
        $this->loadGraphStructure();
    }

    /**
     * Has to be called for every element which is about to be written. If its type is new to the structure, the
     * cached structure is outdated from the moment of the write on, see {@see applyPendingInvalidation()}.
     */
    public function noteType(bool $isNode, string $type): void
    {
        $this->ensureLoaded();
        if (!in_array($type, $isNode ? $this->nodeTypes : $this->relationTypes, true)) {
            $this->invalidationPending = true;
        }
    }

    /**
     * Drops the cached structure if a new type was noted. Call after the elements were written, so that a reload can't
     * read the old structure.
     */
    public function applyPendingInvalidation(): void
    {
        if (!$this->invalidationPending) {
            return;
        }
        $this->invalidationPending = false;
        try {
            $this->redisClient->del([(string) $this->redisKeyFactory->getGraphStructureRedisKey()]);
        } catch (Throwable) {
            // without Redis nothing is cached across requests either
        }
        $this->loaded = false;
        $this->fresh = false;
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded && (count($this->nodeTypes) > 0 || $this->fresh)) {
            return;
        }
        if (!$this->loaded && $this->loadFromRedis()) {
            return;
        }
        $this->loadGraphStructure();
    }

    private function loadFromRedis(): bool
    {
        try {
            $raw = $this->redisClient->get((string) $this->redisKeyFactory->getGraphStructureRedisKey());
            if (!is_string($raw)) {
                return false;
            }
            /** @var array{nodeTypes: string[], relationTypes: string[]} $data */
            $data = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
            $this->nodeTypes = $data['nodeTypes'];
            $this->relationTypes = $data['relationTypes'];
            $this->loaded = true;
            $this->fresh = false;

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        $this->ensureLoaded();

        return $this->nodeTypes;
    }

    /**
     * @return string[]
     */
    public function getRelationTypes(): array
    {
        $this->ensureLoaded();

        return $this->relationTypes;
    }

    public function getTypeFromElasticIndex(string $elasticIndex): ?string
    {
        if (str_starts_with($elasticIndex, 'node_')) {
            return $this->getNodeTypeFromElasticIndex($elasticIndex);
        }
        if (str_starts_with($elasticIndex, 'relation_')) {
            return $this->getRelationTypeFromElasticIndex($elasticIndex);
        }

        return null;
    }

    public function getNodeTypeFromElasticIndex(string $elasticIndex): ?string
    {
        if (!str_starts_with($elasticIndex, 'node_')) {
            throw new Exception(sprintf("Unable to look up node type from Elasticsearch index '%s'; it does not start with 'node_'.", $elasticIndex));
        }
        $truncatedElasticIndex = substr($elasticIndex, 5);
        $find = fn (string $nodeType): bool => strtolower($nodeType) === $truncatedElasticIndex;

        $nodeType = array_find($this->getNodeTypes(), $find);
        if (null === $nodeType && !$this->fresh) {
            // the cached structure may predate the type
            $this->loadGraphStructure();
            $nodeType = array_find($this->nodeTypes, $find);
        }

        return $nodeType;
    }

    public function getRelationTypeFromElasticIndex(string $elasticIndex): ?string
    {
        if (!str_starts_with($elasticIndex, 'relation_')) {
            throw new Exception(sprintf("Unable to look up relation type from Elasticsearch index '%s'; it does not start with 'relation_'.", $elasticIndex));
        }
        $truncatedElasticIndex = substr($elasticIndex, 9);
        $find = fn (string $relationType): bool => strtolower($relationType) === $truncatedElasticIndex;

        $relationType = array_find($this->getRelationTypes(), $find);
        if (null === $relationType && !$this->fresh) {
            // the cached structure may predate the type
            $this->loadGraphStructure();
            $relationType = array_find($this->relationTypes, $find);
        }

        return $relationType;
    }

    public function loadGraphStructure(): void
    {
        $cypherClient = $this->cypherEntityManager->getClient();

        $results = $cypherClient->readTransaction(static function (TransactionInterface $tsx): SummarizedResult {
            return $tsx->run('CALL db.labels() YIELD label RETURN label;');
        });
        /** @var SummarizedResult $results */
        $nodeTypes = [];
        foreach ($results as $result) {
            /** @var CypherMap $result */
            /** @var string $label */
            $label = $result->get('label');
            $nodeTypes[] = $label;
        }

        $results = $cypherClient->readTransaction(static function (TransactionInterface $tsx): SummarizedResult {
            return $tsx->run('CALL db.relationshipTypes() YIELD relationshipType RETURN relationshipType;');
        });
        /** @var SummarizedResult $results */
        $relationTypes = [];
        foreach ($results as $result) {
            /** @var CypherMap $result */
            /** @var string $relationType */
            $relationType = $result->get('relationshipType');
            $relationTypes[] = $relationType;
        }

        $this->nodeTypes = $nodeTypes;
        $this->relationTypes = $relationTypes;
        $this->loaded = true;
        $this->fresh = true;

        $this->writeToRedis();
    }

    private function writeToRedis(): void
    {
        try {
            $this->redisClient->set(
                (string) $this->redisKeyFactory->getGraphStructureRedisKey(),
                json_encode(['nodeTypes' => $this->nodeTypes, 'relationTypes' => $this->relationTypes], JSON_THROW_ON_ERROR),
                'EX',
                self::REDIS_TTL_IN_SECONDS
            );
        } catch (Throwable) {
            // the structure is only cached, not stored
        }
    }
}
