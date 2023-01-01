<?php

namespace App\Service;

use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class AccessChecker
{
    public function __construct(private CypherEntityManager $cypherEntityManager)
    {
    }

    public function checkAccessToNode(UuidInterface $user, UuidInterface $action, UuidInterface $node): bool
    {
        /**
         * @var $res SummarizedResult
         */
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userUuid})\n".
            "MATCH (action:Action {id: \$actionUuid})\n".
            "MATCH (node {id: \$nodeUuid})\n".
            "MATCH p1=(user)-[:PART_OF_GROUP|HAS_RULE*]->(rule:Rule)-[:HAS_ACTION]->(action)\n".
            "MATCH p2=(rule)-[:APPLIES]->()-[:OWNS*0..]->(node)\n".
            'RETURN rule',
            [
                'userUuid' => $user->toString(),
                'actionUuid' => $action->toString(),
                'nodeUuid' => $node->toString(),
            ]
        ));

        return $res->count() > 0;
    }
}
