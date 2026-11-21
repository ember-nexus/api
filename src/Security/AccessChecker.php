<?php

declare(strict_types=1);

namespace App\Security;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\CypherHelper;
use App\Helper\ReadAccessCypherHelper;
use App\Service\AccessResultCacheService;
use App\Service\ElementTypeService;
use App\Type\AccessType;
use App\Type\ElementType;
use App\Type\Uuid;
use Laudis\Neo4j\Databags\Statement;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
#[WithMonologChannel('audit')]
class AccessChecker
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private AuthProvider $authProvider,
        private LoggerInterface $logger,
        private ElementTypeService $elementTypeService,
        private AccessResultCacheService $accessResultCacheService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Escaped label of the node, null if there is no node with this id. Matching nodes by label lets Neo4j use the
     * id index of that label instead of scanning all nodes.
     */
    private function getEscapedNodeLabel(Uuid $nodeId): ?string
    {
        $label = $this->elementTypeService->resolveNodeLabel($nodeId);

        return null === $label ? null : CypherHelper::escapeIdentifier($label);
    }

    /**
     * Escaped type of the relation, null if there is no relation with this id.
     */
    private function getEscapedRelationType(Uuid $relationId): ?string
    {
        $type = $this->elementTypeService->resolveRelationType($relationId);

        return null === $type ? null : CypherHelper::escapeIdentifier($type);
    }

    /**
     * @return Uuid[]
     */
    public function getUsersGroups(Uuid $userId): array
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (user:User {id: $userId})-[:IS_IN_GROUP*1..]->(group:Group) RETURN group.id',
            [
                'userId' => $userId->toString(),
            ]
        ));
        $groupIds = [];
        foreach ($res as $row) {
            $rawGroupId = $row->get('group.id');
            if (!is_string($rawGroupId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property group.id as string, not %s.', get_debug_type($rawGroupId))); // @codeCoverageIgnore
            }
            $groupIds[] = Uuid::fromString($rawGroupId);
        }

        return $groupIds;
    }

    /**
     * Whether the user reaches the node only through ownership and group membership or through read access relations
     * without any condition (onLabel, onParentLabel, onState, onCreatedByUser). Ownership passes such access on to every
     * owned element, so it also holds for all children of the node.
     *
     * @param string $escapedLabel label of the node, already escaped for the use in a query
     */
    public function hasUnconditionalReadAccessToNode(Uuid $userId, Uuid $nodeId, string $escapedLabel): bool
    {
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            'MATCH (element:'.$escapedLabel." {id: \$elementId})\n".
            ReadAccessCypherHelper::unconditionalReadPathMatch('element').
            "WITH path\n".
            "WHERE path IS NOT NULL\n".
            "RETURN count(path) AS count\n".
            'LIMIT 1;',
            [
                'userId' => $userId->toString(),
                'elementId' => $nodeId->toString(),
            ]
        ));

        return $res->count() > 0 && $res->first()->get('count') > 0;
    }

    /**
     * Answers are remembered for the rest of the request, see AccessResultCacheService.
     */
    public function hasAccessToElement(Uuid $userId, Uuid $elementId, AccessType $accessType): bool
    {
        $remembered = $this->accessResultCacheService->get($userId, $elementId, $accessType);
        if (null !== $remembered) {
            return $remembered;
        }
        $hasAccess = $this->checkAccessToElement($userId, $elementId, $accessType);
        if (!$hasAccess) {
            $this->logAccessDenied($userId, $elementId, $accessType);
        }
        $this->accessResultCacheService->set($userId, $elementId, $accessType, $hasAccess);

        return $hasAccess;
    }

    private function checkAccessToElement(Uuid $userId, Uuid $elementId, AccessType $accessType): bool
    {
        if (ElementType::NODE === $this->getElementType($elementId)) {
            if (AccessType::READ === $accessType) {
                return $this->hasReadAccessToNode($userId, $elementId);
            }

            return $this->hasGeneralAccessToNode($userId, $elementId, $accessType);
        }
        if (AccessType::READ === $accessType) {
            return $this->hasReadAccessToRelation($userId, $elementId);
        }
        if (AccessType::UPDATE === $accessType || AccessType::DELETE === $accessType) {
            return $this->hasWriteAccessToRelation($userId, $elementId, $accessType);
        }

        return $this->hasGeneralAccessToRelation($userId, $elementId, $accessType);
    }

    /**
     * Changing or deleting a relation only modifies its start node, therefore the user needs UPDATE/DELETE access to
     * the start node, but only READ access to the end node. This mirrors creating a relation, which requires CREATE
     * access to the start node and READ access to the end node.
     */
    public function hasWriteAccessToRelation(Uuid $userId, Uuid $elementId, AccessType $accessType): bool
    {
        $relationType = $this->getEscapedRelationType($elementId);
        if (null === $relationType) {
            return false;
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (start)-[element:'.$relationType.' {id: $elementId}]->(end) RETURN start.id, end.id',
            [
                'elementId' => $elementId->toString(),
            ]
        ));
        if (1 !== $res->count()) {
            return false;
        }
        $rawStartId = $res->first()->get('start.id');
        $rawEndId = $res->first()->get('end.id');
        if (!is_string($rawStartId) || !is_string($rawEndId)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return properties start.id and end.id as string, not %s and %s.', get_debug_type($rawStartId), get_debug_type($rawEndId))); // @codeCoverageIgnore
        }

        return $this->hasGeneralAccessToNode($userId, Uuid::fromString($rawStartId), $accessType)
            && $this->hasReadAccessToNode($userId, Uuid::fromString($rawEndId));
    }

    /**
     * Logged on the dedicated, always-flushed `audit` channel instead of regular request logging (see monolog.yaml).
     */
    private function logAccessDenied(Uuid $userId, Uuid $elementId, AccessType $accessType): void
    {
        $this->logger->notice(
            'Access to element was denied.',
            [
                'elementId' => $elementId->toString(),
                'accessType' => $accessType->value,
                'client' => [
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $userId->toString(),
                    'token' => $this->authProvider->getTokenId()?->toString(),
                ],
            ]
        );
    }

    /**
     * UPDATE/DELETE require an explicit OWNS/HAS_X_ACCESS relation, not just group membership.
     */
    private function getMinimumAccessRelationsHops(AccessType $accessType): int
    {
        if (AccessType::UPDATE === $accessType || AccessType::DELETE === $accessType) {
            return 1;
        }

        return 0;
    }

    /**
     * Read access of the user to the nodes with the given ids, which all have the given (escaped) label.
     */
    private function buildReadAccessToNodesQuery(string $escapedLabel): string
    {
        return
            "MATCH (user:User {id: \$userId})\n".
            "UNWIND \$ids AS elementId\n".
            'MATCH (element:'.$escapedLabel." {id: elementId})\n".
            ReadAccessCypherHelper::readPathMatch('element').
            "WITH user, element, path\n".
            "WHERE\n".
            "  user.id = element.id\n".
            "  OR path IS NOT NULL\n".
            'RETURN element.id AS elementId;';
    }

    public function hasReadAccessToNode(Uuid $userId, Uuid $elementId): bool
    {
        $label = $this->getEscapedNodeLabel($elementId);
        if (null === $label) {
            return false;
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            'MATCH (element:'.$label." {id: \$elementId})\n".
            ReadAccessCypherHelper::readPathMatch('element').
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

    public function hasGeneralAccessToNode(Uuid $userId, Uuid $elementId, AccessType $accessType): bool
    {
        $label = $this->getEscapedNodeLabel($elementId);
        if (null === $label) {
            return false;
        }
        $minimumRelationsHops = $this->getMinimumAccessRelationsHops($accessType);
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            'MATCH (element:'.$label." {id: \$elementId})\n".
            sprintf('OPTIONAL MATCH (element)<-[relations:OWNS|HAS_%s_ACCESS*%d..]-()<-[:IS_IN_GROUP*0..]-(user)'."\n", $accessType->value, $minimumRelationsHops).
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
            "        element.createdBy = user.id\n".
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

    public function hasReadAccessToRelation(Uuid $userId, Uuid $elementId): bool
    {
        $relationType = $this->getEscapedRelationType($elementId);
        if (null === $relationType) {
            return false;
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            'MATCH (start)-[element:'.$relationType." {id: \$elementId}]->(end)\n".
            'OPTIONAL MATCH (start)<-[startRelations:OWNS|HAS_'.AccessType::READ->value."_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)\n".
            'OPTIONAL MATCH (end)<-[endRelations:OWNS|HAS_'.AccessType::READ->value."_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)\n".
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
            "          start.createdBy = user.id\n".
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
            "          end.createdBy = user.id\n".
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

    public function hasGeneralAccessToRelation(Uuid $userId, Uuid $elementId, AccessType $accessType): bool
    {
        $relationType = $this->getEscapedRelationType($elementId);
        if (null === $relationType) {
            return false;
        }
        $minimumRelationsHops = $this->getMinimumAccessRelationsHops($accessType);
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            'MATCH (start)-[element:'.$relationType." {id: \$elementId}]->(end)\n".
            sprintf('OPTIONAL MATCH (start)<-[startRelations:OWNS|HAS_%s_ACCESS*%d..]-()<-[:IS_IN_GROUP*0..]-(user)'."\n", $accessType->value, $minimumRelationsHops).
            sprintf('OPTIONAL MATCH (end)<-[endRelations:OWNS|HAS_%s_ACCESS*%d..]-()<-[:IS_IN_GROUP*0..]-(user)'."\n", $accessType->value, $minimumRelationsHops).
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
            "          start.createdBy = user.id\n".
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
            "          end.createdBy = user.id\n".
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

    /**
     * Matching a relation only by its id scans all relations, while the start and end node of an element which was
     * just read or written are known: anchoring the match on them only expands the relations of those two nodes.
     */
    private function getRelationMatchClause(string $escapedRelationType, ?Uuid $startId, ?Uuid $endId): string
    {
        return sprintf(
            'MATCH (start%s)-[element:%s {id: $elementId}]->(end%s)',
            null === $startId ? '' : ' {id: $startId}',
            $escapedRelationType,
            null === $endId ? '' : ' {id: $endId}',
        );
    }

    /**
     * @return array<string, string>
     */
    private function getRelationMatchParameters(Uuid $elementId, ?Uuid $startId, ?Uuid $endId): array
    {
        $parameters = ['elementId' => $elementId->toString()];
        if (null !== $startId) {
            $parameters['startId'] = $startId->toString();
        }
        if (null !== $endId) {
            $parameters['endId'] = $endId->toString();
        }

        return $parameters;
    }

    public function getElementType(Uuid $elementId): ?ElementType
    {
        $resolved = $this->elementTypeService->resolve($elementId);
        if (null === $resolved) {
            return null;
        }

        return $resolved->isNode() ? ElementType::NODE : ElementType::RELATION;
    }

    /**
     * @return array<Uuid>
     */
    public function getDirectGroupsWithAccessToElement(Uuid $elementId, AccessType $accessType): array
    {
        $type = $this->getElementType($elementId);
        if (!$type) {
            return [];
        }
        if (ElementType::NODE === $type) {
            return $this->getDirectGroupsWithAccessToNode($elementId, $accessType);
        }

        return $this->getDirectGroupsWithAccessToRelation($elementId, $accessType);
    }

    /**
     * @note will only return direct groups. I.e. if group a has access to the element through group b, then only group
     *       b will be returned.
     *
     * @return array<Uuid>
     *
     * @todo check if element is a group itself?
     */
    public function getDirectGroupsWithAccessToNode(Uuid $elementId, AccessType $accessType): array
    {
        $label = $this->getEscapedNodeLabel($elementId);
        if (null === $label) {
            return [];
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (group:Group)-[relations:OWNS|HAS_'.$accessType->value.'_ACCESS*0..]->(element:'.$label." {id: \$elementId})\n".
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
            $rawGroupId = $row->get('group.id');
            if (!is_string($rawGroupId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property group.id as string, not %s.', get_debug_type($rawGroupId))); // @codeCoverageIgnore
            }
            $groups[] = Uuid::fromString($rawGroupId);
        }

        return $groups;
    }

    /**
     * @note will only return direct groups. I.e. if group a has access to the element through group b, then only group
     *       b will be returned.
     *
     * @return array<Uuid>
     */
    public function getDirectGroupsWithAccessToRelation(Uuid $elementId, AccessType $accessType, ?Uuid $startId = null, ?Uuid $endId = null): array
    {
        $relationType = $this->getEscapedRelationType($elementId);
        if (null === $relationType) {
            return [];
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            $this->getRelationMatchClause($relationType, $startId, $endId)."\n".
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
            $this->getRelationMatchParameters($elementId, $startId, $endId)
        ));
        $groups = [];
        foreach ($res as $row) {
            $rawGroup = $row->get('group');
            if (!is_string($rawGroup)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property group as string, not %s.', get_debug_type($rawGroup))); // @codeCoverageIgnore
            }
            $groups[] = Uuid::fromString($rawGroup);
        }

        return $groups;
    }

    /**
     * @return array<Uuid>
     */
    public function getDirectUsersWithAccessToElement(Uuid $elementId, AccessType $accessType): array
    {
        $type = $this->getElementType($elementId);
        if (!$type) {
            return [];
        }
        if (ElementType::NODE === $type) {
            return $this->getDirectUsersWithAccessToNode($elementId, $accessType);
        }

        return $this->getDirectUsersWithAccessToRelation($elementId, $accessType);
    }

    /**
     * @note will only return direct users. I.e. only users which are connected via OWNS-chain. Also, if the user is
     *       connected via groups & owns *and also* satisfies an onCreatedByUser test.
     *
     * @return array<Uuid>
     */
    public function getDirectUsersWithAccessToNode(Uuid $elementId, AccessType $accessType): array
    {
        $label = $this->getEscapedNodeLabel($elementId);
        if (null === $label) {
            return [];
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            'MATCH (element:'.$label.' {id: $elementId})<-[relations:OWNS|HAS_'.$accessType->value."_ACCESS*0..]-()<-[groups:IS_IN_GROUP*0..]-(user:User)\n".
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
            "          element.createdBy = user.id\n".
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
            "        element.createdBy = user.id\n".
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
            $rawUserId = $row->get('user.id');
            if (!is_string($rawUserId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property user.id as string, not %s.', get_debug_type($rawUserId))); // @codeCoverageIgnore
            }
            $users[] = Uuid::fromString($rawUserId);
        }

        return $users;
    }

    /**
     * @note will only return direct users. I.e. only users which are connected via OWNS-chain. Also, if the user is
     *       connected via groups & owns *and also* satisfies an onCreatedByUser test.
     *
     * @return array<Uuid>
     */
    public function getDirectUsersWithAccessToRelation(Uuid $elementId, AccessType $accessType, ?Uuid $startId = null, ?Uuid $endId = null): array
    {
        $relationType = $this->getEscapedRelationType($elementId);
        if (null === $relationType) {
            return [];
        }
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            $this->getRelationMatchClause($relationType, $startId, $endId)."\n".
            "OPTIONAL MATCH (start)<-[startRelations:OWNS|HAS_SEARCH_ACCESS*1..]-()<-[startGroups:IS_IN_GROUP*0..]-(startUser:User)\n".
            "OPTIONAL MATCH (end)<-[endRelations:OWNS|HAS_READ_ACCESS|HAS_SEARCH_ACCESS*1..]-()<-[endGroups:IS_IN_GROUP*0..]-(endUser:User)\n".
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
            "            start.createdBy = startUser.id\n".
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
            "          start.createdBy = startUser.id\n".
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
            "            end.createdBy = endUser.id\n".
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
            "          end.createdBy = endUser.id\n".
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
            $this->getRelationMatchParameters($elementId, $startId, $endId)
        ));
        $users = [];
        foreach ($res as $row) {
            $rawUser = $row->get('user');
            if (!is_string($rawUser)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property user as string, not %s.', get_debug_type($rawUser))); // @codeCoverageIgnore
            }
            $users[] = Uuid::fromString($rawUser);
        }

        return $users;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     *
     * @param Uuid[] $elementIds
     *
     * @return Uuid[]
     */
    public function checkUserAccessToMultipleElements(Uuid $userId, array $elementIds): array
    {
        /**
         * First find the type of all elements, as well as start and end nodes for relationships. Nodes need no query of
         * their own here, the access query below only returns nodes which exist.
         */
        /** @var array<array{id: string, startId: string|null, endId: string|null}> $data */
        $data = [];
        $relationIdsByType = [];
        $nodeLabels = [];
        foreach ($this->elementTypeService->resolveMany($elementIds) as $rawElementId => $resolved) {
            if (null === $resolved) {
                continue;
            }
            if ($resolved->isNode()) {
                $data[] = ['id' => $rawElementId, 'startId' => null, 'endId' => null];
                $nodeLabels[$rawElementId] = $resolved->getType();
            } else {
                $relationIdsByType[$resolved->getType()][] = $rawElementId;
            }
        }
        foreach ($relationIdsByType as $relationType => $relationIds) {
            $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                sprintf(
                    'UNWIND $ids AS relationId MATCH (start)-[relation:%s {id: relationId}]->(end) RETURN relation.id AS id, start.id AS startId, end.id AS endId',
                    CypherHelper::escapeIdentifier($relationType)
                ),
                ['ids' => $relationIds]
            ));
            foreach ($res as $row) {
                $rawId = $row->get('id');
                $rawStartId = $row->get('startId');
                $rawEndId = $row->get('endId');
                if (!is_string($rawId) || !is_string($rawStartId) || !is_string($rawEndId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return properties id, startId and endId as string, not %s, %s and %s.', get_debug_type($rawId), get_debug_type($rawStartId), get_debug_type($rawEndId))); // @codeCoverageIgnore
                }
                $data[] = ['id' => $rawId, 'startId' => $rawStartId, 'endId' => $rawEndId];
            }
        }

        /**
         * Select all nodes from the dataset such that access queries can be executed on them. The start and end nodes
         * of relations were not resolved yet, they are resolved in one go as well.
         */
        $nodeIds = [];
        foreach ($data as $row) {
            if (null === $row['startId'] || null === $row['endId']) {
                $nodeIds[$row['id']] = $row['id'];
            } else {
                $nodeIds[$row['startId']] = $row['startId'];
                $nodeIds[$row['endId']] = $row['endId'];
            }
        }
        $missingLabels = [];
        foreach ($nodeIds as $rawNodeId) {
            if (!array_key_exists($rawNodeId, $nodeLabels)) {
                $missingLabels[] = Uuid::fromString($rawNodeId);
            }
        }
        foreach ($this->elementTypeService->resolveMany($missingLabels) as $rawNodeId => $resolved) {
            if (null !== $resolved && $resolved->isNode()) {
                $nodeLabels[$rawNodeId] = $resolved->getType();
            }
        }

        /**
         * Execute access query against all nodes, once per label so the id index of that label can be used.
         */
        $nodeIdsByLabel = [];
        foreach ($nodeIds as $rawNodeId) {
            if (array_key_exists($rawNodeId, $nodeLabels)) {
                $nodeIdsByLabel[$nodeLabels[$rawNodeId]][] = $rawNodeId;
            }
        }
        $accessRows = [];
        foreach ($nodeIdsByLabel as $label => $labelNodeIds) {
            $accessRows[] = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                $this->buildReadAccessToNodesQuery(CypherHelper::escapeIdentifier($label)),
                [
                    'userId' => $userId->toString(),
                    'ids' => $labelNodeIds,
                ]
            ));
        }

        /** @var array<string, true> $nodeIdsWithAccess */
        $nodeIdsWithAccess = [];
        foreach ($accessRows as $res) {
            foreach ($res as $row) {
                $rawElementId = $row->get('elementId');
                if (!is_string($rawElementId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property elementId as string, not %s.', get_debug_type($rawElementId))); // @codeCoverageIgnore
                }
                $nodeIdsWithAccess[$rawElementId] = true;
            }
        }

        /**
         * Now filter out nodes from the data array where the user does not have access to all nodes per row.
         */
        /** @var array<string, true> $elementIdsWithAccess */
        $elementIdsWithAccess = [];
        foreach ($data as $row) {
            if (null === $row['startId'] || null === $row['endId']) {
                if (!isset($nodeIdsWithAccess[$row['id']])) {
                    continue;
                }
            } elseif (!isset($nodeIdsWithAccess[$row['startId']]) || !isset($nodeIdsWithAccess[$row['endId']])) {
                continue;
            }
            $elementIdsWithAccess[$row['id']] = true;
        }

        return array_values(array_filter(
            $elementIds,
            static fn (Uuid $elementId): bool => isset($elementIdsWithAccess[$elementId->toString()])
        ));
    }
}
