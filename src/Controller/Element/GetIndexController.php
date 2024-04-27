<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Type\EtagType;
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
        private CollectionService $collectionService,
    ) {
    }

    #[Route(
        '/',
        name: 'get-index',
        methods: ['GET']
    )]
    #[EndpointSupportsEtag(EtagType::INDEX_COLLECTION)]
    public function getIndex(): Response
    {
        $userId = $this->authProvider->getUserId();
        $cypherClient = $this->cypherEntityManager->getClient();
        $res = $cypherClient->runStatement(Statement::create(
            "MATCH (user:User {id: \$userId})\n".
            "MATCH (user)-[:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(element)\n".
            "RETURN element.id\n".
            "ORDER BY element.id\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $userId->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $nodeIds = [];
        foreach ($res as $resultSet) {
            $nodeIds[] = UuidV4::fromString($resultSet->get('element.id'));
        }

        return $this->collectionService->buildCollectionFromIds($nodeIds, [], count($nodeIds));
    }
}
