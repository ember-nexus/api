<?php

namespace App\Security;

use App\Type\AccessType;
use App\Type\ElementType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class AccessChecker
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager
    ) {
    }

    public function hasAccessToElement(UuidInterface $userUuid, UuidInterface $elementUuid, AccessType $accessType): bool
    {
        if (ElementType::NODE === $this->getElementType($elementUuid)) {
            return $this->hasAccessToNode($userUuid, $elementUuid, $accessType);
        } else {
            return $this->hasAccessToRelation($userUuid, $elementUuid, $accessType);
        }
    }

    public function hasAccessToNode(UuidInterface $userUuid, UuidInterface $elementUuid, AccessType $accessType): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (element {id: \$elementId})\n".
            'MATCH (user)-[:IS_IN_GROUP*0..]->()-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*1..]->(element)\n".
            "WHERE\n".
            "  ALL(relation in relations WHERE\n".
            "    type(relation) = \"OWNS\"\n".
            "    OR\n".
            "    (\n".
            '      type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "      AND\n".
            "      (\n".
            "        relation.onLabel IS NULL\n".
            "        OR\n".
            "        relation.onLabel IN labels(element)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onParentLabel IS NULL\n".
            "        OR\n".
            "        relation.onParentLabel IN labels(element)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onState IS NULL\n".
            "        OR\n".
            "        (element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onCreatedByUser IS NULL\n".
            "        OR\n".
            "        (element)<-[:CREATED_BY*]-(user)\n".
            "      )\n".
            "    )\n".
            "  )\n".
            'RETURN user, element;',
            [
                'userId' => $userUuid->toString(),
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return false;
        }

        return true;
    }

    public function hasAccessToRelation(UuidInterface $userUuid, UuidInterface $elementUuid, AccessType $accessType): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (start)-[element {id: \$elementId}]->(end)\n".
            "OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[startRelations:OWNS|HAS_READ_ACCESS*1..]->(start)\n".
            "OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[endRelations:OWNS|HAS_READ_ACCESS*1..]->(end)\n".
            "WHERE\n".
            "  (\n".
            "    user.id = start.id\n".
            "    OR\n".
            "    ALL(startRelation in startRelations WHERE\n".
            "      type(startRelation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            '        type(startRelation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        AND\n".
            "        (\n".
            "          startRelation.onLabel IS NULL\n".
            "          OR\n".
            "          startRelation.onLabel IN labels(start)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          startRelation.onParentLabel IS NULL\n".
            "          OR\n".
            "          startRelation.onParentLabel IN labels(start)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          startRelation.onState IS NULL\n".
            "          OR\n".
            "          (start)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: startRelation.onState})\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          startRelation.onCreatedByUser IS NULL\n".
            "          OR\n".
            "          (start)<-[:CREATED_BY*]-(user)\n".
            "        )\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    user.id = end.id\n".
            "    OR    \n".
            "    ALL(endRelation in endRelations WHERE\n".
            "      type(endRelation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            '        type(endRelation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        AND\n".
            "        (\n".
            "          endRelation.onLabel IS NULL\n".
            "          OR\n".
            "          endRelation.onLabel IN labels(end)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          endRelation.onParentLabel IS NULL\n".
            "          OR\n".
            "          endRelation.onParentLabel IN labels(end)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          endRelation.onState IS NULL\n".
            "          OR\n".
            "          (end)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: endRelation.onState})\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          endRelation.onCreatedByUser IS NULL\n".
            "          OR\n".
            "          (end)<-[:CREATED_BY*]-(user)\n".
            "        )\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH user, element, start, end, startRelations, endRelations\n".
            "WHERE\n".
            "  (\n".
            "    user.id = start.id\n".
            "    OR\n".
            "    startRelations IS NOT NULL\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    user.id = end.id\n".
            "    OR\n".
            "    endRelations IS NOT NULL\n".
            "  )\n".
            'RETURN count(*) as count',
            [
                'userId' => $userUuid->toString(),
                'elementId' => $elementUuid->toString(),
            ]
        ));
        if (1 !== $res->count()) {
            return false;
        }
        if (0 === $res->first()->get('count')) {
            return false;
        }

        return true;
    }

    public function getElementType(UuidInterface $elementUuid): ?ElementType
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
            return ElementType::NODE;
        }
        if (null !== $res->first()->get('relation.id')) {
            return ElementType::RELATION;
        }

        return null;
    }
}
