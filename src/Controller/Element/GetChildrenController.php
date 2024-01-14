<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Service\EtagService;
use App\Type\AccessType;
use App\Type\ElementType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetChildrenController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private CollectionService $collectionService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private EtagService $etagService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/{uuid}/children',
        name: 'get-children',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['GET']
    )]
    public function getChildren(string $uuid): Response
    {
        $parentUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        $type = $this->accessChecker->getElementType($parentUuid);
        if (ElementType::RELATION === $type) {
            // relations can not be parent nodes
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $parentUuid, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $cypherClient = $this->cypherEntityManager->getClient();

        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (parent {id: \$parentId})\n".
            "MATCH (parent)-[r:OWNS]->(child)\n".
            "OPTIONAL MATCH path=(user)-[:IS_IN_GROUP*0..]->()-[:OWNS|HAS_READ_ACCESS*0..]->(child)\n".
            "WHERE\n".
            "  user.id = child.id\n".
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
            "        relation.onLabel IN labels(child)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onParentLabel IS NULL\n".
            "        OR\n".
            "        relation.onParentLabel IN labels(child)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onState IS NULL\n".
            "        OR\n".
            "        (child)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onCreatedByUser IS NULL\n".
            "        OR\n".
            "        (child)<-[:CREATED_BY*]-(user)\n".
            "      )\n".
            "    )\n".
            "  )\n".
            "WITH user, r, child, path\n".
            "ORDER BY child.id, r.id\n".
            "WHERE\n".
            "  user.id = child.id\n".
            "  OR\n".
            "  path IS NOT NULL\n".
            "WITH child, collect(DISTINCT r.id) AS rCol\n".
            "WITH child, collect(child.id) + collect(rCol) AS row\n".
            "WITH collect(DISTINCT row) AS allRows, count(child) AS totalCount\n".
            "UNWIND allRows AS row\n".
            "RETURN row[0] AS child, row[1] AS r, totalCount\n".
            "ORDER BY child\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $userUuid->toString(),
                'parentId' => $parentUuid->toString(),
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
                $nodeUuids[] = UuidV4::fromString($resultSet->get('child'));
                foreach ($resultSet->get('r') as $relationId) {
                    $relationUuids[] = UuidV4::fromString($relationId);
                }
            }
        }

        $response = $this->collectionService->buildCollectionFromUuids($nodeUuids, $relationUuids, $totalCount);

        $etag = $this->etagService->getChildrenCollectionEtag($parentUuid);
        if ($etag) {
            $response->headers->set('Etag', $etag);
        }

        return $response;
    }
}
