<?php

namespace App\Controller\User;

use App\Exception\ClientUnauthorizedException;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetTokenController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private AuthProvider $authProvider,
        private CollectionService $collectionService
    ) {
    }

    #[Route(
        '/token',
        name: 'get-token',
        methods: ['GET']
    )]
    public function getToken(): Response
    {
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw new ClientUnauthorizedException();
        }

        if ($this->authProvider->isAnonymous()) {
            throw new ClientUnauthorizedException();
        }

        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:OWNS]->(token:Token)\n".
            "RETURN token.id\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $userUuid->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $tokenUuids = [];
        foreach ($res as $resultSet) {
            $tokenUuids[] = UuidV4::fromString($resultSet->get('token.id'));
        }

        return $this->collectionService->buildCollectionFromUuids($tokenUuids, [], count($tokenUuids));
    }
}
