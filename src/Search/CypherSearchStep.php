<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\SearchStepInterface;
use App\Contract\SearchStepResultInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\SearchStepResult;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class CypherSearchStep implements SearchStepInterface
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory
    ) {
    }

    public function isDangerous(): bool
    {
        return true;
    }

    public function getIdentifier(): string
    {
        return 'cypher';
    }

    public function executeStep(array|string $query, array $parameters): SearchStepResultInterface
    {
        if (!is_string($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'string', $query);
        }

        $results = $this->cypherEntityManager->getClient()->readTransaction(static function (TransactionInterface $tsx) use ($query, $parameters) {
            return $tsx->run($query, $parameters);
        });
        $results = $results->toArray();
        if (1 === count($results)) {
            $results = $results[0]->toArray();
        }

        $searchStepResult = new SearchStepResult();
        $searchStepResult->setResults($results);
        $searchStepResult->setDebugData(
            $this->getIdentifier(), [
                'query' => $query,
                'parameters' => $parameters,
            ]);

        return $searchStepResult;
    }
}
