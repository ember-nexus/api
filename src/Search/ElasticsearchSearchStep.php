<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\SearchStepInterface;
use App\Contract\SearchStepResultInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\CollectionService;
use App\Type\SearchStepResult;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class ElasticsearchSearchStep implements SearchStepInterface
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElasticEntityManager $elasticEntityManager,
        private CollectionService $collectionService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory
    ) {
    }

    public function isDangerous(): bool
    {
        return false;
    }

    public function executeStep(array|string $query, array $parameters): SearchStepResultInterface
    {
        if (!is_array($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'array', $query);
        }

        $currentUserId = $this->authProvider->getUserId();

        $userGroups = $this->accessChecker->getUsersGroups($currentUserId);
        $stringUserGroups = [];
        foreach ($userGroups as $userGroup) {
            $stringUserGroups[] = $userGroup->toString();
        }

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

        $page = 1;
        $pageSize = 25;
        if (array_key_exists('page', $parameters)) {
            $page = $parameters['page'];
            if (!is_numeric($page)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('page', 'integer', $page);
            }
        }
        if (array_key_exists('pageSize', $parameters)) {
            $pageSize = $parameters['pageSize'];
            if (!is_numeric($pageSize)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('pageSize', 'integer', $pageSize);
            }
        }

        $combinedQuery = [
            '_source' => [
                '_id',
            ],
            'from' => ($page - 1) *$pageSize,
            'size' =>$pageSize,
            'query' => [
                'bool' => [
                    'must' => [
                        $query,
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
        ];

        /**
         * @psalm-suppress InvalidArgument
         */
        $res = $this->elasticEntityManager->getClient()->search([
            'index' => $indices,
            'body' => $combinedQuery,
        ]);

        if (!($res instanceof Elasticsearch)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Unknown response type for elastic search query.', ['response' => $res]);
        }

//        print_r($res->asArray());

        $elementIds = [];
        foreach ($res->asArray()['hits']['hits'] as $element) {
            $elementIds[] = UuidV4::fromString($element['_id']);
        }
        $totalElements = $res->asArray()['hits']['total']['value'];

        $searchStepResult = new SearchStepResult();
        $searchStepResult->setResults([
            'elementIds' => $elementIds,
            'totalElements' => $totalElements,
        ]);
        $searchStepResult->setDebugData([
            'query' => $query,
            'combinedQuery' => $combinedQuery,
            'parameters' => $parameters,
            'indices' => $indices,
        ]);

        return $searchStepResult;
    }
}
