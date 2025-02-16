<?php

declare(strict_types=1);

namespace App\Security;

use App\Type\AccessType;
use App\Type\ElementType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class AccessChecker
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
    ) {
    }

    /**
     * @return UuidInterface[]
     */
    public function getUsersGroups(UuidInterface $userId): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (user:User {id: $userId})-[:IS_IN_GROUP*1..]->(group:Group) RETURN group.id',
            [
                'userId' => $userId->toString(),
            ]
        ));
        $groupIds = [];
        foreach ($res as $row) {
            $groupIds[] = UuidV4::fromString($row['group.id']);
        }

        return $groupIds;
    }

    public function hasAccessToElement(UuidInterface $userId, UuidInterface $elementId, AccessType $accessType): bool
    {
        if (ElementType::NODE === $this->getElementType($elementId)) {
            if (AccessType::READ === $accessType) {
                return $this->hasReadAccessToNode($userId, $elementId);
            } else {
                return $this->hasGeneralAccessToNode($userId, $elementId, $accessType);
            }
        } else {
            if (AccessType::READ === $accessType) {
                return $this->hasReadAccessToRelation($userId, $elementId);
            } else {
                return $this->hasGeneralAccessToRelation($userId, $elementId, $accessType);
            }
        }
    }

    public function hasReadAccessToNode(UuidInterface $userId, UuidInterface $elementId): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (element {id: \$elementId})\n".
            "OPTIONAL MATCH path=(user)-[:IS_IN_GROUP*0..]->()-[:OWNS|HAS_READ_ACCESS*0..]->(element)\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
            "  ALL(relation in relationships(path) WHERE\n".
            "    type(relation) = \"IS_IN_GROUP\"\n".
            "    OR\n".
            "    type(relation) = \"OWNS\"\n".
            "    OR\n".
            "    (\n".
            "      type(relation) = \"HAS_READ_ACCESS\"\n".
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
            "WITH user, element, path\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
            "  path IS NOT NULL\n".
            'RETURN count(*) as count;',
            [
                'userId' => $userId->toString(),
                'elementId' => $elementId->toString(),
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

    public function hasGeneralAccessToNode(UuidInterface $userId, UuidInterface $elementId, AccessType $accessType): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (element {id: \$elementId})\n".
            'OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*1..]->(element)\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
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
            "WITH user, element, relations\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
            "  relations IS NOT NULL\n".
            'RETURN count(*) as count;',
            [
                'userId' => $userId->toString(),
                'elementId' => $elementId->toString(),
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

    public function hasReadAccessToRelation(UuidInterface $userId, UuidInterface $elementId): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (start)-[element {id: \$elementId}]->(end)\n".
            'OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[startRelations:OWNS|HAS_'.AccessType::READ->value."_ACCESS*0..]->(start)\n".
            'OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[endRelations:OWNS|HAS_'.AccessType::READ->value."_ACCESS*0..]->(end)\n".
            "WHERE\n".
            "  (\n".
            "    user.id = start.id\n".
            "    OR\n".
            "    ALL(startRelation in startRelations WHERE\n".
            "      type(startRelation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            '        type(startRelation) = "HAS_'.AccessType::READ->value."_ACCESS\"\n".
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
            "        (\n".
            "          type(endRelation) = \"HAS_READ_ACCESS\"\n".
            "          OR\n".
            '          type(endRelation) = "'.AccessType::READ->value."\"\n".
            "        )\n".
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
            'RETURN count(*) as count;',
            [
                'userId' => $userId->toString(),
                'elementId' => $elementId->toString(),
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

    public function hasGeneralAccessToRelation(UuidInterface $userId, UuidInterface $elementId, AccessType $accessType): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (start)-[element {id: \$elementId}]->(end)\n".
            'OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[startRelations:OWNS|HAS_'.$accessType->value."_ACCESS*1..]->(start)\n".
            'OPTIONAL MATCH (user)-[:IS_IN_GROUP*0..]->()-[endRelations:OWNS|HAS_'.$accessType->value."_ACCESS*1..]->(end)\n".
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
            "        (\n".
            "          type(endRelation) = \"HAS_READ_ACCESS\"\n".
            "          OR\n".
            '          type(endRelation) = "'.$accessType->value."\"\n".
            "        )\n".
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
            'RETURN count(*) as count;',
            [
                'userId' => $userId->toString(),
                'elementId' => $elementId->toString(),
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

    public function getElementType(UuidInterface $elementId): ?ElementType
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "OPTIONAL MATCH (node {id: \$elementId})\n".
            "OPTIONAL MATCH ()-[relation {id: \$elementId}]-()\n".
            'RETURN node.id, relation.id',
            [
                'elementId' => $elementId->toString(),
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

    /**
     * @return array<UuidInterface>
     */
    public function getDirectGroupsWithAccessToElement(UuidInterface $elementId, AccessType $accessType): array
    {
        $type = $this->getElementType($elementId);
        if (!$type) {
            return [];
        }
        if (ElementType::NODE === $type) {
            return $this->getDirectGroupsWithAccessToNode($elementId, $accessType);
        } else {
            return $this->getDirectGroupsWithAccessToRelation($elementId, $accessType);
        }
    }

    /**
     * @note will only return direct groups. I.e. if group a has access to the element through group b, then only group
     *       b will be returned.
     *
     * @todo check if element is a group itself?
     *
     * @return array<UuidInterface>
     */
    public function getDirectGroupsWithAccessToNode(UuidInterface $elementId, AccessType $accessType): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (group:Group)-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*0..]->(element {id: \$elementId})\n".
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
            "      relation.onCreatedByUser IS NULL\n".
            "    )\n".
            "  )\n".
            'RETURN group.id;',
            [
                'elementId' => $elementId->toString(),
            ]
        ));
        $groups = [];
        foreach ($res as $row) {
            $groups[] = UuidV4::fromString($row->get('group.id'));
        }

        return $groups;
    }

    /**
     * @note will only return direct groups. I.e. if group a has access to the element through group b, then only group
     *       b will be returned.
     *
     * @return array<UuidInterface>
     */
    public function getDirectGroupsWithAccessToRelation(UuidInterface $elementId, AccessType $accessType): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (start)-[element {id: \$elementId}]->(end)\n".
            "OPTIONAL MATCH (startGroup:Group)-[startRelations:OWNS|HAS_SEARCH_ACCESS*1..]->(start)\n".
            "OPTIONAL MATCH (endGroup:Group)-[endRelations:OWNS|HAS_READ_ACCESS|HAS_SEARCH_ACCESS*1..]->(end)\n".
            "WHERE\n".
            "  (\n".
            "    startGroup.id = start.id\n".
            "    OR\n".
            "    ALL(relation in startRelations WHERE\n".
            "      type(relation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            '        type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        AND\n".
            "        (\n".
            "          relation.onLabel IS NULL\n".
            "          OR\n".
            "          relation.onLabel IN labels(start)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onParentLabel IS NULL\n".
            "          OR\n".
            "          relation.onParentLabel IN labels(start)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onState IS NULL\n".
            "          OR\n".
            "          (start)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "        )\n".
            "        AND\n".
            "        relation.onCreatedByUser IS NULL\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    endGroup.id = end.id\n".
            "    OR\n".
            "    ALL(relation in endRelations WHERE\n".
            "      type(relation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            "        (\n".
            "          type(relation) = \"HAS_READ_ACCESS\"\n".
            "          OR\n".
            '          type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onLabel IS NULL\n".
            "          OR\n".
            "          relation.onLabel IN labels(end)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onParentLabel IS NULL\n".
            "          OR\n".
            "          relation.onParentLabel IN labels(end)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onState IS NULL\n".
            "          OR\n".
            "          (end)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "        )\n".
            "        AND\n".
            "        relation.onCreatedByUser IS NULL\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH element, start, end, startGroup, endGroup, startRelations, endRelations\n".
            "WHERE\n".
            "  startGroup.id = endGroup.id\n".
            "  AND\n".
            "  (\n".
            "    start.id = element.id\n".
            "    OR\n".
            "    startRelations IS NOT NULL\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    end.id = element.id\n".
            "    OR\n".
            "    endRelations IS NOT NULL\n".
            "  )\n".
            'RETURN DISTINCT startGroup.id AS group;',
            [
                'elementId' => $elementId->toString(),
            ]
        ));
        $groups = [];
        foreach ($res as $row) {
            $groups[] = UuidV4::fromString($row->get('group'));
        }

        return $groups;
    }

    /**
     * @return array<UuidInterface>
     */
    public function getDirectUsersWithAccessToElement(UuidInterface $elementId, AccessType $accessType): array
    {
        $type = $this->getElementType($elementId);
        if (!$type) {
            return [];
        }
        if (ElementType::NODE === $type) {
            return $this->getDirectUsersWithAccessToNode($elementId, $accessType);
        } else {
            return $this->getDirectUsersWithAccessToRelation($elementId, $accessType);
        }
    }

    /**
     * @note will only return direct users. I.e. only users which are connected via OWNS-chain. Also, if the user is
     *       connected via groups & owns *and also* satisfies an onCreatedByUser test.
     *
     * @return array<UuidInterface>
     */
    public function getDirectUsersWithAccessToNode(UuidInterface $elementId, AccessType $accessType): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (user:User)-[groups:IS_IN_GROUP*0..]->()-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*0..]->(element {id: \$elementId})\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
            "  (\n".
            "    ALL(relation in relations WHERE\n".
            "      type(relation) = \"OWNS\"\n".
            "      OR\n".
            "      (\n".
            '        type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        AND\n".
            "        (\n".
            "          relation.onLabel IS NULL\n".
            "          OR\n".
            "          relation.onLabel IN labels(element)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onParentLabel IS NULL\n".
            "          OR\n".
            "          relation.onParentLabel IN labels(element)\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onState IS NULL\n".
            "          OR\n".
            "          (element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "        )\n".
            "        AND\n".
            "        (\n".
            "          relation.onCreatedByUser IS NULL\n".
            "          OR\n".
            "          (element)<-[:CREATED_BY*]-(user)\n".
            "        )\n".
            "      )\n".
            "    )\n".
            "    AND\n".
            "    (\n".
            "      isEmpty(groups)\n".
            "      OR\n".
            "      ANY(relation in relations WHERE\n".
            '        type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "        AND\n".
            "        (element)<-[:CREATED_BY*]-(user)\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH user, element, relations\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR\n".
            "  relations IS NOT NULL\n".
            'RETURN user.id;',
            [
                'elementId' => $elementId->toString(),
            ]
        ));
        $users = [];
        foreach ($res as $row) {
            $users[] = UuidV4::fromString($row->get('user.id'));
        }

        return $users;
    }

    /**
     * @note will only return direct users. I.e. only users which are connected via OWNS-chain. Also, if the user is
     *       connected via groups & owns *and also* satisfies an onCreatedByUser test.
     *
     * @return array<UuidInterface>
     */
    public function getDirectUsersWithAccessToRelation(UuidInterface $elementId, AccessType $accessType): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (start)-[element {id: \$elementId}]->(end)\n".
            "OPTIONAL MATCH (startUser:User)-[startGroups:IS_IN_GROUP*0..]->()-[startRelations:OWNS|HAS_SEARCH_ACCESS*1..]->(start)\n".
            "OPTIONAL MATCH (endUser:User)-[endGroups:IS_IN_GROUP*0..]->()-[endRelations:OWNS|HAS_READ_ACCESS|HAS_SEARCH_ACCESS*1..]->(end)\n".
            "WHERE\n".
            "  (\n".
            "    startUser.id = start.id\n".
            "    OR\n".
            "    (\n".
            "      ALL(relation in startRelations WHERE\n".
            "        type(relation) = \"OWNS\"\n".
            "        OR\n".
            "        (\n".
            '          type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "          AND\n".
            "          (\n".
            "            relation.onLabel IS NULL\n".
            "            OR\n".
            "            relation.onLabel IN labels(start)\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onParentLabel IS NULL\n".
            "            OR\n".
            "            relation.onParentLabel IN labels(start)\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onState IS NULL\n".
            "            OR\n".
            "            (start)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onCreatedByUser IS NULL\n".
            "            OR\n".
            "            (start)<-[:CREATED_BY*]-(startUser)\n".
            "          )\n".
            "        )\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        isEmpty(startGroups)\n".
            "        OR\n".
            "        ANY(relation in startRelations WHERE\n".
            '          type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "          AND\n".
            "          (start)<-[:CREATED_BY*]-(startUser)\n".
            "        )\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    endUser.id = start.id\n".
            "    OR\n".
            "    (\n".
            "      ALL(relation in endRelations WHERE\n".
            "        type(relation) = \"OWNS\"\n".
            "        OR\n".
            "        (\n".
            "          (\n".
            "            type(relation) = \"HAS_READ_ACCESS\"\n".
            "            OR\n".
            '            type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onLabel IS NULL\n".
            "            OR\n".
            "            relation.onLabel IN labels(end)\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onParentLabel IS NULL\n".
            "            OR\n".
            "            relation.onParentLabel IN labels(end)\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onState IS NULL\n".
            "            OR\n".
            "            (end)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "          )\n".
            "          AND\n".
            "          (\n".
            "            relation.onCreatedByUser IS NULL\n".
            "            OR\n".
            "            (end)<-[:CREATED_BY*]-(endUser)\n".
            "          )\n".
            "        )\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        isEmpty(endGroups)\n".
            "        OR\n".
            "        ANY(relation in endRelations WHERE\n".
            "          (\n".
            "            type(relation) = \"HAS_READ_ACCESS\"\n".
            "            OR\n".
            '            type(relation) = "HAS_'.$accessType->value."_ACCESS\"\n".
            "          )\n".
            "          AND\n".
            "          (end)<-[:CREATED_BY*]-(endUser)\n".
            "        )\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH element, start, end, startUser, endUser, startRelations, endRelations\n".
            "WHERE\n".
            "  startUser.id = endUser.id\n".
            "  AND\n".
            "  (\n".
            "    start.id = startUser.id\n".
            "    OR\n".
            "    startRelations IS NOT NULL\n".
            "  )\n".
            "  AND\n".
            "  (\n".
            "    end.id = endUser.id\n".
            "    OR\n".
            "    endRelations IS NOT NULL\n".
            "  )\n".
            'RETURN DISTINCT startUser.id AS user;',
            [
                'elementId' => $elementId->toString(),
            ]
        ));
        $users = [];
        foreach ($res as $row) {
            $users[] = UuidV4::fromString($row->get('user'));
        }

        return $users;
    }
}
