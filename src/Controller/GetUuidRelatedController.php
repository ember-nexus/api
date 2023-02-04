<?php

namespace App\Controller;

use App\Helper\Regex;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\UnicodeString;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetUuidRelatedController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private CollectionService $collectionService,
        private AuthProvider $authProvider
    ) {
    }

    #[Route(
        '/{uuid}/related',
        name: 'getUuidRelated',
        requirements: [
            'uuid' => Regex::UUID_V4,
        ],
        methods: ['GET']
    )]
    public function getUuidRelated(string $uuid): Response
    {
        $centerUuid = UuidV4::fromString($uuid);
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (center {id: \$centerId})\n".
            "MATCH (center)-[]-(outer)\n".
            'RETURN count(outer) AS count, labels(outer) AS labels',
            [
                'centerId' => $centerUuid->toString(),
            ]
        ));
        if (0 === $res->count()) {
            return $this->collectionService->buildCollectionFromUuids([], [], 0);
        }
        $labels = $res->first()->get('labels');
        $totalCount = $res->first()->get('count');

        $permissionQueries = [];
        foreach ($labels as $label) {
            $permissionQueries[] = sprintf(
                '(user)-[:PART_OF_GROUP*0..]->()-[:OWNS|READ_PERMISSION|READ_PERMISSION_ON_%s*]->(outer)',
                (new UnicodeString($label))
                    ->snake()
                    ->upper()
            );
        }
        $permissionQueries = 'WHERE '.implode("\nOR ", $permissionQueries);

        $res = $cypherClient->runStatement(Statement::create(
            sprintf(
                "MATCH (user {id: \$userId})\n".
                "MATCH (center {id: \$centerId})\n".
                "MATCH (center)-[r]-(outer)\n".
                "%s\n".
                "RETURN outer.id, collect(r.id), count(outer) AS totalCount\n".
                "ORDER BY outer.id\n".
                "SKIP \$skip\n".
                'LIMIT $limit',
                $permissionQueries
            ),
            [
                'userId' => $this->authProvider->getUserUuid()->toString(),
                'centerId' => $centerUuid->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $nodeUuids = [];
        $relationUuids = [];
        foreach ($res as $resultSet) {
            $nodeUuids[] = UuidV4::fromString($resultSet->get('outer.id'));
            foreach ($resultSet->get('collect(r.id)') as $relationId) {
                $relationUuids[] = UuidV4::fromString($relationId);
            }
        }

        return $this->collectionService->buildCollectionFromUuids($nodeUuids, $relationUuids, $totalCount);
    }
}
