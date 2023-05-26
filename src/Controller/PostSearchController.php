<?php

namespace App\Controller;

use App\Exception\ClientUnauthorizedException;
use App\Exception\ServerException;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Service\ElementManager;
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
        private ElementManager $elementManager,
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

        //        $body = [
        //            'match' => [
        //                'note' => 'contains token',
        //            ],
        //        ];

        $res = $this->elasticEntityManager->getClient()->search([
            'index' => '*',
            'body' => [
                '_source' => [
                    '_id',
                ],
                'query' => [
                    'bool' => [
                        'must' => [
                            $body,
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
            throw new ServerException('Unknown response type for elastic search query.');
        }

        $nodeIds = [];
        $relationIds = [];

        foreach ($res->asArray()['hits']['hits'] as $element) {
            if (str_starts_with($element['_index'], 'node_')) {
                $nodeIds[] = UuidV4::fromString($element['_id']);
            } else {
                $relationIds[] = UuidV4::fromString($element['_id']);
            }
        }

        return $this->collectionService->buildCollectionFromUuids($nodeIds, $relationIds);
    }
}
