<?php

declare(strict_types=1);

namespace App\EventSystem\SearchQuery\EventListener;

use App\EventSystem\SearchQuery\Event\SearchQueryEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Type\SearchQueryType;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class ElasticsearchSearchQueryEventListener
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElasticEntityManager $elasticEntityManager,
        private CollectionService $collectionService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory
    ) {
    }

    public function onSearchQueryEvent(SearchQueryEvent $event): void
    {
        if (SearchQueryType::ELASTICSEARCH !== $event->getSearchQueryType()) {
            return;
        }

        $currentUserId = $this->authProvider->getUserId();

        $userGroups = $this->accessChecker->getUsersGroups($currentUserId);
        $stringUserGroups = [];
        foreach ($userGroups as $userGroup) {
            $stringUserGroups[] = $userGroup->toString();
        }

        //        $rawQueryObject = $event->getSearchQuery();
        //        if (!is_array($rawQueryObject)) {
        //            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'array', $rawQueryObject);
        //        }
        //        if (!array_key_exists('query', $rawQueryObject)) {
        //            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('query.query', 'array');
        //        }
        //        $userQuery = $rawQueryObject['query'];

        $userQuery = $event->getSearchQuery();

        $parameters = $event->getParameters();

        $nodeTypes = [];
        if (array_key_exists('nodeTypes', $parameters)) {
            $rawNodeTypes = $parameters['nodeTypes'];
            if (!is_array($rawNodeTypes)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('nodeTypes', 'array', $rawNodeTypes);
            }
            foreach ($rawNodeTypes as $rawNodeType) {
                $nodeTypes[] = sprintf('node_%s', strtolower($rawNodeType));
            }
        }

        $relationTypes = [];
        if (array_key_exists('relationTypes', $parameters)) {
            $rawRelationTypes = $parameters['relationTypes'];
            if (!is_array($rawRelationTypes)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('relationTypes', 'array', $rawRelationTypes);
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
                                                    'value' => $currentUserId->toString(),
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
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Unknown response type for elastic search query.', ['response' => $res]);
        }

        print_r($res->asArray());

        $elementIds = [];
        foreach ($res->asArray()['hits']['hits'] as $element) {
            $elementIds[] = UuidV4::fromString($element['_id']);
        }
        $totalElements = $res->asArray()['hits']['total']['value'];

        //        return $this->collectionService->buildElementCollectionFromIds($elementIds, $totalElements);

        $event->setResult([
            'elementIds' => $elementIds,
        ]);
        $event->setNumberOfResults($totalElements);
        $event->stopPropagation();
    }
}
