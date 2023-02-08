<?php

namespace App\Controller;

use App\Exception\ClientNotFoundException;
use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Security\PermissionChecker;
use App\Service\CollectionService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\UnicodeString;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetChildrenController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private CollectionService $collectionService,
        private AuthProvider $authProvider,
        private PermissionChecker $permissionChecker
    ) {
    }

    #[Route(
        '/{uuid}/children',
        name: 'getChildren',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getChildren(string $uuid): Response
    {
        $parentUuid = UuidV4::fromString($uuid);
        $hasUserReadPermissionToParentElement = $this->permissionChecker->checkPermissionToNode(
            $this->authProvider->getUserUuid(),
            $parentUuid,
            'READ'
        );
        if (!$hasUserReadPermissionToParentElement) {
            throw new ClientNotFoundException();
        }
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (parent {id: \$parentId})\n".
            "MATCH (parent)-[:OWNS]->(child)\n".
            'RETURN count(child) AS count, labels(child) AS labels',
            [
                'parentId' => $parentUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return $this->collectionService->buildEmptyCollection();
        }
        $labels = $res->first()->get('labels');
        $totalCount = $res->first()->get('count');

        $permissionQueries = [];
        foreach ($labels as $label) {
            $permissionQueries[] = sprintf(
                '(user)-[:PART_OF_GROUP*0..]->()-[:OWNS|READ_PERMISSION|READ_PERMISSION_ON_%s*]->(child)',
                (new UnicodeString($label))
                    ->snake()
                    ->upper()
            );
        }
        $permissionQueries = 'WHERE '.implode("\nOR ", $permissionQueries);

        $res = $cypherClient->runStatement(Statement::create(
            sprintf(
                "MATCH (user {id: \$userId})\n".
                "MATCH (parent {id: \$parentId})\n".
                "MATCH (parent)-[:OWNS]->(child)\n".
                "MATCH (parent)-[r]-(child)\n".
                "%s\n".
                "RETURN child.id, collect(r.id), count(child) AS totalCount\n".
                "ORDER BY child.id\n".
                "SKIP \$skip\n".
                'LIMIT $limit',
                $permissionQueries
            ),
            [
                'userId' => $this->authProvider->getUserUuid()->toString(),
                'parentId' => $parentUuid->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $nodeUuids = [];
        $relationUuids = [];
        foreach ($res as $resultSet) {
            $nodeUuids[] = UuidV4::fromString($resultSet->get('child.id'));
            foreach ($resultSet->get('collect(r.id)') as $relationId) {
                $relationUuids[] = UuidV4::fromString($relationId);
            }
        }

        return $this->collectionService->buildCollectionFromUuids($nodeUuids, $relationUuids, $totalCount);
    }
}
