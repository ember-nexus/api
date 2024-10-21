<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Type\EtagType;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;
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
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
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
            "WITH count(element.id) AS totalCount, collect(element.id) as elementIds\n".
            "UNWIND elementIds AS elementId\n".
            "WITH elementId, totalCount\n".
            "ORDER BY elementId\n".
            "SKIP \$skip\n".
            "LIMIT \$limit\n".
            'RETURN collect(elementId) AS elementIds, totalCount',
            [
                'userId' => $userId->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $totalCount = 0;
        $nodeIds = [];
        if (count($res) > 0) {
            $totalCount = $res->first()->get('totalCount');
            if (!is_int($totalCount)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property totalCount as int, not %s.', get_debug_type($totalCount))); // @codeCoverageIgnore
            }
            $rawElementIds = $res->first()->get('elementIds');
            if (!($rawElementIds instanceof CypherList)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property elementIds as CypherList, not %s.', get_debug_type($rawElementIds))); // @codeCoverageIgnore
            }
            foreach ($rawElementIds as $elementId) {
                $nodeIds[] = UuidV4::fromString($elementId);
            }
        }

        return $this->collectionService->buildCollectionFromIds($nodeIds, [], $totalCount);
    }
}
