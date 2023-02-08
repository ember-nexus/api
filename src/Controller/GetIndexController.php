<?php

namespace App\Controller;

use App\Security\AuthProvider;
use App\Service\CollectionService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetIndexController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private AuthProvider $authProvider,
        private CollectionService $collectionService
    ) {
    }

    #[Route(
        '/',
        name: 'getIndex',
        methods: ['GET']
    )]
    public function getIndex(): Response
    {
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:PART_OF_GROUP*0..]->()-[:OWNS]->(element)\n".
            "RETURN element.id\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $this->authProvider->getUserUuid()->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $nodeUuids = [];
        foreach ($res as $resultSet) {
            $nodeUuids[] = UuidV4::fromString($resultSet->get('element.id'));
        }

        return $this->collectionService->buildCollectionFromUuids($nodeUuids, [], count($nodeUuids));
    }
}
