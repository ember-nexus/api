<?php

namespace App\Controller;

use App\Exception\ClientBadRequestException;
use App\Exception\ClientUnauthorizedException;
use App\Exception\ServerException;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class PostSearchController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElasticEntityManager $elasticEntityManager,
        private CollectionService $collectionService
    ) {
    }

    #[Route(
        '/search',
        name: 'postSearch',
        methods: ['POST']
    )]
    public function postSearch(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $currentUserUuid = $this->authProvider->getUserUuid();
        if (!$currentUserUuid) {
            throw new ClientUnauthorizedException();
        }

        $userGroups = $this->accessChecker->getUsersGroups($currentUserUuid);
        $stringUserGroups = [];
        foreach ($userGroups as $userGroup) {
            $stringUserGroups[] = $userGroup->toString();
        }

        if (!array_key_exists('query', $body)) {
            throw new ClientBadRequestException(detail: "Body property 'query' is required.");
        }
        $userQuery = $body['query'];

        $nodeTypes = [];
        if (array_key_exists('nodeTypes', $body)) {
            $rawNodeTypes = $body['nodeTypes'];
            if (!is_array($rawNodeTypes)) {
                throw new ClientBadRequestException(detail: 'Property "nodeTypes" must be an array of node types.');
            }
            foreach ($rawNodeTypes as $rawNodeType) {
                $nodeTypes[] = sprintf('node_%s', strtolower($rawNodeType));
            }
        }

        $relationTypes = [];
        if (array_key_exists('relationTypes', $body)) {
            $rawRelationTypes = $body['relationTypes'];
            if (!is_array($rawRelationTypes)) {
                throw new ClientBadRequestException(detail: 'Property "relationTypes" must be an array of relation types.');
            }
            foreach ($rawRelationTypes as $rawRelationType) {
                $relationTypes[] = sprintf('relation_%s', strtolower($rawRelationType));
            }
        }

        $indices = [...$nodeTypes, ...$relationTypes];
        /**
         * @psalm-suppress TypeDoesNotContainType
         */
        if (0 === count($indices)) {
            $indices = '*';
        } else {
            $indices = implode(',', $indices);
        }

        /**
         * @psalm-suppress InvalidArgument
         */
        $res = $this->elasticEntityManager->getClient()->search([
            'index' => $indices,
            'body' => [
                '_source' => [
                    '_id',
                ],
                'from' => ($this->collectionService->getCurrentPage() - 1) * $this->collectionService->getPageSize(),
                'size' => $this->collectionService->getPageSize(),
                'query' => [
                    'bool' => [
                        'must' => [
                            $userQuery,
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'terms' => [
                                                '_groupsWithSearchAccess.keyword' => $stringUserGroups,
                                            ],
                                        ],
                                        [
                                            'term' => [
                                                '_usersWithSearchAccess.keyword' => [
                                                    'value' => $currentUserUuid->toString(),
                                                ],
                                            ],
                                        ],
                                    ],
                                    'minimum_should_match' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        if (!($res instanceof Elasticsearch)) {
            throw new ServerException(detail: 'Unknown response type for elastic search query.');
        }

        $elementIds = [];
        foreach ($res->asArray()['hits']['hits'] as $element) {
            $elementIds[] = UuidV4::fromString($element['_id']);
        }
        $totalElements = $res->asArray()['hits']['total']['value'];

        return $this->collectionService->buildUnifiedCollectionFromUuids($elementIds, $totalElements);
    }
}
