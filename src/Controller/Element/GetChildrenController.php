<?php

declare(strict_types=1);

namespace App\Controller\Element;

use App\Attribute\EndpointSupportsEtag;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\CypherHelper;
use App\Helper\ReadAccessCypherHelper;
use App\Helper\Regex;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Service\ElementTypeService;
use App\Type\AccessType;
use App\Type\ElementType;
use App\Type\EtagType;
use App\Type\Response\CollectionResponse;
use App\Type\Uuid;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class GetChildrenController extends AbstractController
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private CollectionService $collectionService,
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementTypeService $elementTypeService,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    #[Route(
        '/{id}/children',
        name: 'get-children',
        requirements: [
            'id' => Regex::UUID_CONTROLLER,
        ],
        methods: ['GET']
    )]
    #[EndpointSupportsEtag(EtagType::CHILDREN_COLLECTION)]
    public function getChildren(string $id): CollectionResponse
    {
        $parentId = Uuid::fromString($id);
        $userId = $this->authProvider->getUserId();

        $type = $this->accessChecker->getElementType($parentId);
        if (ElementType::RELATION === $type) {
            // relations can not be parent nodes
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (!$this->accessChecker->hasAccessToElement($userId, $parentId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $label = $this->elementTypeService->resolveNodeLabel($parentId);
        if (null === $label) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $cypherClient = $this->cypherEntityManager->getClient();

        $escapedLabel = CypherHelper::escapeIdentifier($label);
        if ($this->accessChecker->hasUnconditionalReadAccessToNode($userId, $parentId, $escapedLabel)) {
            // the user owns the parent (directly or via groups) or reads it through relations without any condition:
            // ownership passes that access on to every child, so the children need no access check of their own
            $accessClause =
                'MATCH (parent:'.$escapedLabel." {id: \$parentId})\n".
                "MATCH (parent)-[r:OWNS]->(child)\n".
                "WITH r, child\n".
                "ORDER BY child.id, r.id\n";
        } else {
            $accessClause =
                "MATCH (user:User {id: \$userId})\n".
                'MATCH (parent:'.$escapedLabel." {id: \$parentId})\n".
                "MATCH (parent)-[r:OWNS]->(child)\n".
                ReadAccessCypherHelper::readPathMatch('child').
                "WITH user, r, child, path\n".
                "ORDER BY child.id, r.id\n".
                "WHERE\n".
                "  user.id = child.id\n".
                "  OR\n".
                "  path IS NOT NULL\n";
        }

        $res = $cypherClient->runStatement(Statement::create(
            $accessClause.
            "WITH child, collect(DISTINCT r.id) AS rCol\n".
            "WITH child, collect(child.id) + collect(rCol) AS row\n".
            "WITH collect(DISTINCT row) AS allRows, count(child) AS totalCount\n".
            "UNWIND allRows AS row\n".
            "RETURN row[0] AS child, row[1] AS r, totalCount\n".
            "ORDER BY child\n".
            "SKIP \$skip\n".
            'LIMIT $limit',
            [
                'userId' => $userId->toString(),
                'parentId' => $parentId->toString(),
                'skip' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'limit' => $this->collectionService->getPageSize(),
            ]
        ));
        $totalCount = 0;
        $nodeIds = [];
        $relationIds = [];
        if (count($res) > 0) {
            $totalCount = $res->first()->get('totalCount');
            if (!is_int($totalCount)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property totalCount as int, not %s.', get_debug_type($totalCount))); // @codeCoverageIgnore
            }
            foreach ($res as $resultSet) {
                $rawChild = $resultSet->get('child');
                if (!is_string($rawChild)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property child as string, not %s.', get_debug_type($rawChild))); // @codeCoverageIgnore
                }
                $nodeIds[] = Uuid::fromString($rawChild);
                $rawRelations = $resultSet->get('r');
                if (!($rawRelations instanceof CypherList)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r as CypherList, not %s.', get_debug_type($rawRelations))); // @codeCoverageIgnore
                }
                foreach ($rawRelations as $relationId) {
                    if (!is_string($relationId)) {
                        throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r.item as string, not %s.', get_debug_type($relationId))); // @codeCoverageIgnore
                    }
                    $relationIds[] = Uuid::fromString($relationId);
                }
            }
        }

        return $this->collectionService->buildCollectionFromIds($nodeIds, $relationIds, $totalCount);
    }
}
