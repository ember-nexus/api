<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class GraphStructureService
{
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
    ) {
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        if (0 === count($this->nodeTypes)) {
            $this->loadGraphStructure();
        }

        return $this->nodeTypes;
    }

    /**
     * @return string[]
     */
    public function getRelationTypes(): array
    {
        if (0 === count($this->relationTypes)) {
            $this->loadGraphStructure();
        }

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
        $nodeTypes = $this->getNodeTypes();

        return array_find($nodeTypes, fn ($nodeType) => strtolower($nodeType) === $truncatedElasticIndex);
    }

    public function getRelationTypeFromElasticIndex(string $elasticIndex): ?string
    {
        if (!str_starts_with($elasticIndex, 'relation_')) {
            throw new Exception(sprintf("Unable to look up relation type from Elasticsearch index '%s'; it does not start with 'relation_'.", $elasticIndex));
        }
        $truncatedElasticIndex = substr($elasticIndex, 9);
        $relationTypes = $this->getRelationTypes();

        return array_find($relationTypes, fn ($relationType) => strtolower($relationType) === $truncatedElasticIndex);
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
            $nodeTypes[] = $result->get('label');
        }

        $results = $cypherClient->readTransaction(static function (TransactionInterface $tsx): SummarizedResult {
            return $tsx->run('CALL db.relationshipTypes() YIELD relationshipType RETURN relationshipType;');
        });
        /** @var SummarizedResult $results */
        $relationTypes = [];
        foreach ($results as $result) {
            /** @var CypherMap $result */
            $relationTypes[] = $result->get('relationshipType');
        }

        $this->nodeTypes = $nodeTypes;
        $this->relationTypes = $relationTypes;
    }
}
