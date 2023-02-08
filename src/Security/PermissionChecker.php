<?php

namespace App\Security;

use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\String\UnicodeString;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PermissionChecker
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager
    ) {
    }

    /**
     * Todo: check labels on security relevant operations, e.g. WRITE @ Users.
     *
     * @param string $permission e.g. 'READ', 'CREATE', 'WRITE
     */
    public function checkPermissionToElement(
        UuidInterface $userUuid,
        UuidInterface $elementUuid,
        string $permission
    ): bool {
        $isNode = $this->checkIsNode($elementUuid);
        if (null === $isNode) {
            return false;
        }
        if ($isNode) {
            return $this->checkPermissionToNode($userUuid, $elementUuid, $permission);
        }

        return $this->checkPermissionToRelation($userUuid, $elementUuid, $permission);
    }

    public function checkIsNode(UuidInterface $elementUuid): ?bool
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementId}]-()\n".
            'RETURN node.id, relation.id',
            [
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return null;
        }
        if (null !== $res->first()->get('node.id')) {
            return true;
        }
        if (null !== $res->first()->get('relation.id')) {
            return false;
        }

        return null;
    }

    public function checkPermissionToNode(
        UuidInterface $userUuid,
        UuidInterface $nodeUuid,
        string $permission
    ): bool {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (node {id: \$nodeId})\n".
            'RETURN node',
            [
                'nodeId' => $nodeUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return false;
        }
        $node = $res->first()->get('node');
        $permissionTypes = [];
        foreach ($node->getLabels() as $label) {
            $permissionTypes[] = sprintf(
                '%s_PERMISSION_ON_%s',
                $permission,
                (new UnicodeString($label))
                    ->snake()
                    ->upper()
            );
        }
        $permissionTypes = implode('|', $permissionTypes);
        $res2 = $cypherClient->runStatement(Statement::create(
            sprintf(
                "MATCH (node {id: \$nodeId})\n".
                "MATCH (user:User {id: \$userId})\n".
                "MATCH (user)-[:PART_OF_GROUP*0..]->()-[:OWNS|%s_PERMISSION|%s*]->(node)\n".
                'RETURN node, user',
                $permission,
                $permissionTypes
            ),
            [
                'userId' => $userUuid->toString(),
                'nodeId' => $nodeUuid->toString(),
            ]
        ));
        if (0 === $res2->count()) {
            return false;
        }

        return true;
    }

    public function checkPermissionToRelation(
        UuidInterface $userUuid,
        UuidInterface $relationUuid,
        string $permission
    ): bool {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (start)-[relation {id: \$relationId}]->(end)\n".
            'RETURN start, end, relation',
            [
                'relationId' => $relationUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return false;
        }
        $start = $res->first()->get('start');
        $end = $res->first()->get('end');

        $startPermissionTypes = [];
        foreach ($start->getLabels() as $label) {
            $startPermissionTypes[] = sprintf(
                '%s_PERMISSION_ON_%s',
                $permission,
                (new UnicodeString($label))
                    ->snake()
                    ->upper()
            );
        }
        $startPermissionTypes = implode('|', $startPermissionTypes);

        $endPermissionTypes = [];
        foreach ($end->getLabels() as $label) {
            $endPermissionTypes[] = sprintf(
                '%s_PERMISSION_ON_%s',
                $permission,
                (new UnicodeString($label))
                    ->snake()
                    ->upper()
            );
        }
        $endPermissionTypes = implode('|', $endPermissionTypes);

        $res2 = $cypherClient->runStatement(Statement::create(
            sprintf(
                "MATCH (start {id: \$startId})\n".
                "MATCH (end {id: \$endId})\n".
                "MATCH (user:User {id: \$userId})\n".
                "MATCH (user)-[:PART_OF_GROUP*0..]->()-[:OWNS|%s_PERMISSION|%s*]->(start)\n".
                "MATCH (user)-[:PART_OF_GROUP*0..]->()-[:OWNS|%s_PERMISSION|%s*]->(end)\n".
                'RETURN start, end, user',
                $permission,
                $startPermissionTypes,
                $permission,
                $endPermissionTypes
            ),
            [
                'userId' => $userUuid->toString(),
                'startId' => UuidV4::fromString($start->properties()->get('id'))->toString(),
                'endId' => UuidV4::fromString($end->properties()->get('id'))->toString(),
            ]
        ));
        if (0 === $res2->count()) {
            return false;
        }

        return true;
    }
}
