<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\SearchStepInterface;
use App\Contract\SearchStepResultInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Type\SearchStepResult;
use Exception;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Path;
use Laudis\Neo4j\Types\Relationship;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class CypherPathSearchStep implements SearchStepInterface
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
    ) {
    }

    public function isDangerous(): bool
    {
        return true;
    }

    public function getIdentifier(): string
    {
        return 'cypherPath';
    }

    public function executeStep(array|string $query, array $parameters): SearchStepResultInterface
    {
        if (!is_string($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'string', $query);
        }

        $forbiddenKeywords = [
            'CASE',
            'WHEN',
            'THEN',
            'ELSE',
            'END',
            'IF',
            'IS',
            'apoc',
        ];
        // todo: ignore this check if user has elevated permissions
        foreach ($forbiddenKeywords as $forbiddenKeyword) {
            if (str_contains(strtolower($query), strtolower($forbiddenKeyword))) {
                throw $this->client403ForbiddenExceptionFactory->createFromTemplate(sprintf("Unable to execute potentially unsafe cypher query containing keyword '%s'.", $forbiddenKeyword));
            }
        }

        // todo check if limit check is required or even useful. correctly implemented it can be great, but it can also
        // todo be easily forged. maybe recommendation inside return body? or inside debug body?
        //        if (str_contains(strtolower($query), 'limit')) {
        //            throw new Exception('Cypher query must contain limit clause in order to be executed');
        //        }

        /**
         * @var $results CypherMap[]
         */
        $results = $this->cypherEntityManager->getClient()->readTransaction(static function (TransactionInterface $tsx) use ($query, $parameters) {
            return $tsx->run($query, $parameters);
        });

        if (count($results) > 100) {
            throw new Exception('Reached limit of paths to be returned internally. Use offset based pagination with limit 100 to fix this.');
        }

        $paths = [];
        foreach ($results as $result) {
            if (!$result->hasKey('path')) {
                throw new Exception("Expected result set to contain property 'path'.");
            }
            $path = $result->get('path');
            if (!($path instanceof Path)) {
                throw new Exception("Expected variable 'path' to be of type 'Path'.");
            }
            $paths[] = $path;
        }
        /**
         * @var $paths Path[]
         */

        // check paths for direct access

        $checkedPaths = [];

        $lists = [];
        foreach ($paths as $path) {
            $list = [];
            $nodes = $path->getNodes();
            $relations = $path->getRelationships();
            /**
             * @var $nodes Node[]
             * @var $relations Relationship[]
             */
            for ($i = 0; $i <= count($nodes) - 2; ++$i) {
                $list[] = $nodes[$i]->getProperty('id');
                $list[] = $relations[$i]->getProperty('id');
            }
            $list[] = $nodes[count($nodes) - 1]->getProperty('id');
            $lists[] = $list;
        }

        //        $query = "MATCH (user:User {id: \$userId})\n".
        //            "MATCH (center {id: \$centerId})\n".
        //            "MATCH (center)-[r]-(outer)\n".
        //            "OPTIONAL MATCH path=(user)-[:IS_IN_GROUP*0..]->()-[:OWNS|HAS_READ_ACCESS*0..]->(outer)\n".
        //            "WHERE\n".
        //            "  user.id = outer.id\n".
        //            "  OR\n".
        //            "  ALL(relation in relationships(path) WHERE\n".
        //            "    type(relation) = \"IS_IN_GROUP\"\n".
        //            "    OR\n".
        //            "    type(relation) = \"OWNS\"\n".
        //            "    OR\n".
        //            "    (\n".
        //            "      type(relation) = \"HAS_READ_ACCESS\"\n".
        //            "      AND\n".
        //            "      (\n".
        //            "        relation.onLabel IS NULL\n".
        //            "        OR\n".
        //            "        relation.onLabel IN labels(outer)\n".
        //            "      )\n".
        //            "      AND\n".
        //            "      (\n".
        //            "        relation.onParentLabel IS NULL\n".
        //            "        OR\n".
        //            "        relation.onParentLabel IN labels(outer)\n".
        //            "      )\n".
        //            "      AND\n".
        //            "      (\n".
        //            "        relation.onState IS NULL\n".
        //            "        OR\n".
        //            "        (outer)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
        //            "      )\n".
        //            "      AND\n".
        //            "      (\n".
        //            "        relation.onCreatedByUser IS NULL\n".
        //            "        OR\n".
        //            "        (outer)<-[:CREATED_BY*]-(user)\n".
        //            "      )\n".
        //            "    )\n".
        //            "  )\n".
        //            "WITH user, r, outer, path\n".
        //            "ORDER BY outer.id, r.id\n".
        //            "WHERE\n".
        //            "  user.id = outer.id\n".
        //            "  OR\n".
        //            "  path IS NOT NULL\n".
        //            "WITH outer, collect(DISTINCT r.id) AS rCol\n".
        //            "WITH outer, collect(outer.id) + collect(rCol) AS row\n".
        //            "WITH collect(DISTINCT row) AS allRows, count(outer) AS totalCount\n".
        //            "UNWIND allRows AS row\n".
        //            "RETURN row[0] AS outer, row[1] AS r, totalCount\n".
        //            "ORDER BY outer\n".
        //            "SKIP \$skip\n".
        //            'LIMIT $limit';
        //        echo $query;

        //        print_r($paths);
        //        exit;

        //        if (1 === count($results)) {
        //            $results = $results[0]->toArray();
        //        }

        $searchStepResult = new SearchStepResult();
        $searchStepResult->setResults([]);
        $searchStepResult->setDebugData(
            $this->getIdentifier(), [
                'query' => $query,
                'parameters' => $parameters,
                'lists' => $lists,
            ]);

        return $searchStepResult;
    }
}
