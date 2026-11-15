<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\EventListener;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ExpressionService;
use App\Service\GraphStructureService;
use App\Type\SearchStepType;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class ElasticsearchQueryDslMixinSearchStepEventListener
{
    public const SearchStepType TYPE = SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN;

    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElasticEntityManager $elasticEntityManager,
        private ExpressionService $expressionService,
        private GraphStructureService $graphStructureService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory,
    ) {
    }

    private function getIndices(SearchStepEvent $event): ?string
    {
        $parameters = $event->getParameters();
        $nodeTypes = [];
        if (array_key_exists('nodeTypes', $parameters)) {
            $rawNodeTypes = $parameters['nodeTypes'];
            if (!is_array($rawNodeTypes)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('nodeTypes', 'array', $rawNodeTypes);
            }
            foreach ($rawNodeTypes as $index => $rawNodeType) {
                if (!is_string($rawNodeType)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('nodeTypes[%d]', $index), 'string', $rawNodeType);
                }
                $nodeTypes[] = sprintf('node_%s', strtolower($rawNodeType));
            }
        }

        $relationTypes = [];
        if (array_key_exists('relationTypes', $parameters)) {
            $rawRelationTypes = $parameters['relationTypes'];
            if (!is_array($rawRelationTypes)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('relationTypes', 'array', $rawRelationTypes);
            }
            foreach ($rawRelationTypes as $index => $rawRelationType) {
                if (!is_string($rawRelationType)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('relationTypes[%d]', $index), 'string', $rawRelationType);
                }
                $relationTypes[] = sprintf('relation_%s', strtolower($rawRelationType));
            }
        }

        $indices = [...$nodeTypes, ...$relationTypes];
        $indices = array_values(array_unique($indices));
        sort($indices);

        /**
         * @psalm-suppress TypeDoesNotContainType
         */
        if (0 === count($indices)) {
            return '*';
        }

        // remove unknown indices
        $indicesWhichExist = [];
        foreach ($indices as $index) {
            if (null !== $this->graphStructureService->getTypeFromElasticIndex($index)) {
                $indicesWhichExist[] = $index;
            }
        }

        if (0 === count($indicesWhichExist)) {
            // no requested index exists in the database, but indices _were_ requested
            // -> the query would return zero results
            // -> do _not_ return '*'
            return null;
        }

        return implode(',', $indicesWhichExist);
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param array<string, mixed> $array
     * @param array<string, mixed> $parameters
     */
    private function executeExpressionsInQuery(array &$array, array $parameters): void
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $this->executeExpressionsInQuery($value, $parameters);
            } elseif (is_string($value)) {
                if (str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
                    $expression = substr($value, 2, -2);
                    $array[$key] = $this->expressionService->runExpression($expression, $parameters);
                }
            }
        }
    }

    /**
     * @return array{hits: array{total: array{value: int}, max_score: float|null, hits: list<array{_id: string, _index: string, _score: float|null}>}}
     */
    private function getResponseData(Elasticsearch $response): array
    {
        /** @var array{hits: array{total: array{value: int}, max_score: float|null, hits: list<array{_id: string, _index: string, _score: float|null}>}} $data */
        $data = $response->asArray();

        return $data;
    }

    /**
     * @return array<mixed>
     */
    private function formatElementResults(Elasticsearch $response): array
    {
        $elements = [];
        foreach ($this->getResponseData($response)['hits']['hits'] as $element) {
            $elements[] = [
                'id' => UuidV4::fromString($element['_id']),
                'type' => $this->graphStructureService->getTypeFromElasticIndex($element['_index']),
                'metadata' => [
                    'score' => $element['_score'],
                ],
            ];
        }

        return $elements;
    }

    /**
     * @return array<string, mixed>
     */
    private function getQueryFromEvent(SearchStepEvent $event): array
    {
        $query = $event->getQuery();
        if (!is_array($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'array', $query);
        }

        return $query;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getPageFromParameters(array $parameters): int
    {
        if (!array_key_exists('page', $parameters)) {
            return 1;
        }
        if (!is_int($parameters['page'])) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('page', 'integer', $parameters['page']);
        }

        return $parameters['page'];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getPageSizeFromParameters(array $parameters): int
    {
        if (!array_key_exists('pageSize', $parameters)) {
            return 25;
        }
        if (!is_int($parameters['pageSize'])) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('pageSize', 'integer', $parameters['pageSize']);
        }

        return $parameters['pageSize'];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function getMinScoreFromParameters(array $parameters): int|float|null
    {
        if (!array_key_exists('minScore', $parameters)) {
            return null;
        }
        $minScore = $parameters['minScore'];
        if (!is_int($minScore) && !is_float($minScore) && null !== $minScore) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('minScore', 'integer|float', $minScore);
        }

        return $minScore;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCombinedQuery(SearchStepEvent $event): array
    {
        $query = $this->getQueryFromEvent($event);
        $parameters = $event->getParameters();

        $this->executeExpressionsInQuery($query, $parameters);

        $page = $this->getPageFromParameters($parameters);
        $pageSize = $this->getPageSizeFromParameters($parameters);
        $minScore = $this->getMinScoreFromParameters($parameters);

        $currentUserId = $this->authProvider->getUserId();

        $userGroups = $this->accessChecker->getUsersGroups($currentUserId);
        $stringUserGroups = [];
        foreach ($userGroups as $userGroup) {
            $stringUserGroups[] = $userGroup->toString();
        }

        $combinedQuery = [
            '_source' => [
                '_id',
            ],
            'from' => ($page - 1) * $pageSize,
            'size' => $pageSize,
            'min_score' => $minScore,
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
        if (null === $minScore) {
            unset($combinedQuery['min_score']);
        }

        return $combinedQuery;
    }

    #[AsEventListener]
    public function onSearchStepEvent(SearchStepEvent $event): void
    {
        if (self::TYPE !== $event->getType()) {
            return;
        }

        $event->addDebugData('step-handler', sprintf('%s (%s)', self::TYPE->value, self::class));

        $start = microtime(true);
        $event->addDebugData('start', $start);

        $indices = $this->getIndices($event);

        if (null === $indices) {
            $event->addDebugData('noRequestIndicesExist', 'Notice: None of the requested indices were found in the database, therefore short-circuiting the search request to zero results.');
            $event->setResults([
                'elements' => [],
                'totalHits' => 0,
                'maxScore' => 0,
            ]);
            $event->stopPropagation();

            return;
        }

        $combinedQuery = $this->buildCombinedQuery($event);
        $event->addDebugData('indices', $indices);
        $event->addDebugData('combinedQuery', $combinedQuery);

        /**
         * @psalm-suppress InvalidArgument
         */
        $response = $this->elasticEntityManager->getClient()->search([
            'index' => $indices,
            'body' => $combinedQuery,
        ]);

        if (!($response instanceof Elasticsearch)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Unknown response type for elastic search query.', ['response' => $response]);
        }

        $event->setResults([
            'elements' => $this->formatElementResults($response),
            'totalHits' => $this->getResponseData($response)['hits']['total']['value'],
            'maxScore' => $this->getResponseData($response)['hits']['max_score'],
        ]);

        $end = microtime(true);
        $event->addDebugData('end', $end);
        $event->addDebugData('duration', round($end - $start, 6));
        $event->stopPropagation();
    }
}
