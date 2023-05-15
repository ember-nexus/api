<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Type\AccessType;
use App\Type\ElementType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetParentsController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private CollectionService $collectionService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker
    ) {
    }

    #[Route(
        '/{uuid}/parents',
        name: 'getParents',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getParents(string $uuid): Response
    {
        $childUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        $type = $this->accessChecker->getElementType($childUuid);
        if (ElementType::RELATION === $type) {
            // relations can not be child nodes
            throw new ClientNotFoundException();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $childUuid, AccessType::READ)) {
            throw new ClientNotFoundException();
        }

        $cypherClient = $this->cypherEntityManager->getClient();

        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (child {id: \$childId})\n".
            "MATCH (child)<-[r:OWNS]-(parent)\n".
            "OPTIONAL MATCH path=(user)-[:IS_IN_GROUP*0..]->()-[:OWNS|HAS_READ_ACCESS*0..]->(parent)\n".
            "WHERE\n".
            "  user.id = parent.id\n".
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
            "        relation.onLabel IN labels(parent)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onParentLabel IS NULL\n".
            "        OR\n".
            "        relation.onParentLabel IN labels(parent)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onState IS NULL\n".
            "        OR\n".
            "        (parent)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onCreatedByUser IS NULL\n".
            "        OR\n".
            "        (parent)<-[:CREATED_BY*]-(user)\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH user, r, parent, path\n".
            "WHERE\n".
            "  user.id = parent.id\n".
            "  OR\n".
            "  path IS NOT NULL\n".
            "RETURN parent.id, collect(r.id), count(parent) AS totalCount\n".
            "ORDER BY parent.id\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $userUuid->toString(),
                'childId' => $childUuid->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $totalCount = 0;
        $nodeUuids = [];
        $relationUuids = [];
        if (count($res) > 0) {
            $totalCount = $res->first()->get('totalCount');
            foreach ($res as $resultSet) {
                $nodeUuids[] = UuidV4::fromString($resultSet->get('parent.id'));
                foreach ($resultSet->get('collect(r.id)') as $relationId) {
                    $relationUuids[] = UuidV4::fromString($relationId);
                }
            }
        }

        return $this->collectionService->buildCollectionFromUuids($nodeUuids, $relationUuids, $totalCount);
    }
}
