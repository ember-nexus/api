<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\SearchStep\EventListener;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\EventSystem\SearchStep\EventListener\ElasticsearchQueryDslMixinSearchStepEventListener;
use App\Exception\Client400BadContentException;
use App\Exception\Server500InternalServerErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ExpressionService;
use App\Service\GraphStructureService;
use App\Type\SearchStepType;
use Beste\Psr\Log\TestLogger;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Response\Elasticsearch;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

#[Small]
#[CoversClass(ElasticsearchQueryDslMixinSearchStepEventListener::class)]
#[AllowMockObjectsWithoutExpectations]
class ElasticsearchQueryDslMixinSearchStepEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function buildElasticsearchQueryDslMixinSearchStepEventListener(
        ?AuthProvider $authProvider = null,
        ?AccessChecker $accessChecker = null,
        ?ElasticEntityManager $elasticEntityManager = null,
        ?ExpressionService $expressionService = null,
        ?GraphStructureService $graphStructureService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory = null,
    ): ElasticsearchQueryDslMixinSearchStepEventListener {
        $authProvider = $authProvider ?? $this->prophesize(AuthProvider::class)->reveal();
        $accessChecker = $accessChecker ?? $this->prophesize(AccessChecker::class)->reveal();
        $elasticEntityManager = $elasticEntityManager ?? $this->prophesize(ElasticEntityManager::class)->reveal();
        $expressionService = $expressionService ?? $this->prophesize(ExpressionService::class)->reveal();
        $graphStructureService = $graphStructureService ?? $this->prophesize(GraphStructureService::class)->reveal();
        $client400BadContentExceptionFactory = $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal();
        $server500InternalServerErrorExceptionFactory = $server500InternalServerErrorExceptionFactory ?? $this->prophesize(Server500InternalServerErrorExceptionFactory::class)->reveal();

        return new ElasticsearchQueryDslMixinSearchStepEventListener(
            $authProvider,
            $accessChecker,
            $elasticEntityManager,
            $expressionService,
            $graphStructureService,
            $client400BadContentExceptionFactory,
            $server500InternalServerErrorExceptionFactory
        );
    }

    public function testGetIndicesReturnsStarIfNoIndicesAreDefined(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getIndices');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [],
            []
        );

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);

        $this->assertSame('*', $result);
    }

    public function testGetIndicesReturnsIndicesStringWhenProvidedWithIndices(): void
    {
        $graphStructureService = $this->createMock(GraphStructureService::class);
        $graphStructureService
            ->method('getTypeFromElasticIndex')
            ->willReturnCallback(static function (string $index): string {
                return match ($index) {
                    'node_data' => 'Data',
                    'node_taxon' => 'Taxon',
                    'node_plant' => 'Plant',
                    'relation_is_member_of' => 'IS_MEMBER_OF',
                    'relation_has_tag' => 'HAS_TAG',
                    'relation_owns' => 'OWNS',
                    default => throw new InvalidArgumentException(sprintf('Unexpected index: %s', $index)),
                };
            });
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            graphStructureService: $graphStructureService
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getIndices');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [],
            [
                'nodeTypes' => ['Data', 'Taxon', 'Plant'],
                'relationTypes' => ['IS_MEMBER_OF', 'HAS_TAG', 'OWNS'],
            ]
        );

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);

        $this->assertSame('node_data,node_plant,node_taxon,relation_has_tag,relation_is_member_of,relation_owns', $result);
    }

    public function testGetIndicesFailsWhenNodeTypesIsNotArray(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getIndices');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [],
            [
                'nodeTypes' => 'something else',
            ]
        );

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'nodeTypes' to be array, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetIndicesFailsWhenRelationTypesIsNotArray(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getIndices');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [],
            [
                'relationTypes' => 'something else',
            ]
        );

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'relationTypes' to be array, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testExecuteExpressionsInQueryIgnoresNormalStrings(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'executeExpressionsInQuery');

        $originalArray = [
            'key' => 'value',
            'nested' => [
                'otherKey' => 'other value',
                'prefixMatch' => '{{something else',
                'suffixMatch' => 'something else}}',
            ],
        ];
        $executedArray = $originalArray;
        $parameters = [];

        $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [&$executedArray, $parameters]);

        $this->assertEquals($originalArray, $executedArray);
    }

    public function testExecuteExpressionsInQueryExecutesExpressionsProperly(): void
    {
        $originalArray = [
            'key' => '{{"some expression"}}',
        ];
        $executedArray = $originalArray;
        $parameters = ['a' => 'b'];

        $expressionService = $this->prophesize(ExpressionService::class);
        $expressionService
            ->runExpression(Argument::is('"some expression"'), Argument::is($parameters))
            ->shouldBeCalledOnce()
            ->willReturn('result');

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            expressionService: $expressionService->reveal()
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'executeExpressionsInQuery');

        $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [&$executedArray, $parameters]);

        $this->assertNotEquals($originalArray, $executedArray);
        $this->assertSame('result', $executedArray['key']);
    }

    public function testFormatElementResults(): void
    {
        $response = $this->prophesize(Elasticsearch::class);
        $response
            ->asArray()
            ->shouldBeCalledOnce()
            ->willReturn([
                'hits' => [
                    'hits' => [
                        [
                            '_id' => '55f1e41a-a5e1-4efd-a93c-fb115e76c68b',
                            '_index' => 'node_data',
                            '_score' => 2.1,
                        ],
                        [
                            '_id' => '0c6cd0ce-07ca-4326-8f82-2d0834d34de5',
                            '_index' => 'node_plant',
                            '_score' => 1.7,
                        ],
                        [
                            '_id' => 'e877ffae-d756-44c0-b5b1-60a049e247c0',
                            '_index' => 'relation_owns',
                            '_score' => 0.3,
                        ],
                    ],
                ],
            ]);

        $graphStructureService = $this->prophesize(GraphStructureService::class);
        $graphStructureService
            ->getTypeFromElasticIndex('node_data')
            ->shouldBeCalledOnce()
            ->willReturn('Data');
        $graphStructureService
            ->getTypeFromElasticIndex('node_plant')
            ->shouldBeCalledOnce()
            ->willReturn('Plant');
        $graphStructureService
            ->getTypeFromElasticIndex('relation_owns')
            ->shouldBeCalledOnce()
            ->willReturn('OWNS');

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            graphStructureService: $graphStructureService->reveal()
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'formatElementResults');

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$response->reveal()]);

        $this->assertCount(3, $result);
        $this->assertSame('55f1e41a-a5e1-4efd-a93c-fb115e76c68b', (string) $result[0]['id']);
        $this->assertSame('0c6cd0ce-07ca-4326-8f82-2d0834d34de5', (string) $result[1]['id']);
        $this->assertSame('e877ffae-d756-44c0-b5b1-60a049e247c0', (string) $result[2]['id']);
        $this->assertSame('Data', $result[0]['type']);
        $this->assertSame('Plant', $result[1]['type']);
        $this->assertSame('OWNS', $result[2]['type']);
        $this->assertSame(2.1, $result[0]['metadata']['score']);
        $this->assertSame(1.7, $result[1]['metadata']['score']);
        $this->assertSame(0.3, $result[2]['metadata']['score']);
    }

    public function testGetQueryFromEventReturnsQueryOfTypeArray(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getQueryFromEvent');

        $query = [
            'key' => 'value',
        ];
        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            $query,
            []
        );

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);

        $this->assertEquals($query, $result);
    }

    public function testGetQueryFromEventFailsOnNullQuery(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getQueryFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            null,
            []
        );

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'query' to be array, got null.", $exception->getDetail());
        }
    }

    public function testGetQueryFromEventFailsOnStringQuery(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getQueryFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            'some string query',
            []
        );

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'query' to be array, got string 'some string query'.", $exception->getDetail());
        }
    }

    public function testGetPageFromParametersReturnsDefaultValueIfNoParameterIsSet(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageFromParameters');

        $parameters = [];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(1, $result);
    }

    public function testGetPageFromParametersReturnsParameterIfSet(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageFromParameters');

        $parameters = [
            'page' => 1234,
        ];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(1234, $result);
    }

    public function testGetPageFromParametersFailsIfPageParameterIsNotOfTypeInt(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageFromParameters');

        $parameters = [
            'page' => 'something else',
        ];

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'page' to be integer, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetPageSizeFromParametersReturnsDefaultValueIfNoParameterIsSet(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageSizeFromParameters');

        $parameters = [];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(25, $result);
    }

    public function testGetPageSizeFromParametersReturnsParameterIfSet(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageSizeFromParameters');

        $parameters = [
            'pageSize' => 100,
        ];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(100, $result);
    }

    public function testGetPageSizeFromParametersFailsIfPageParameterIsNotOfTypeInt(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getPageSizeFromParameters');

        $parameters = [
            'pageSize' => 'something else',
        ];

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'pageSize' to be integer, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testGetMinScoreFromParametersReturnsDefaultValueIfNoParameterIsSet(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getMinScoreFromParameters');

        $parameters = [];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertNull($result);
    }

    public function testGetMinScoreFromParametersReturnsParameterIfSetToInt(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getMinScoreFromParameters');

        $parameters = [
            'minScore' => 10,
        ];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(10, $result);
    }

    public function testGetMinScoreFromParametersReturnsParameterIfSetToFloat(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getMinScoreFromParameters');

        $parameters = [
            'minScore' => 0.23,
        ];
        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);

        $this->assertEquals(0.23, $result);
    }

    public function testGetMinScoreFromParametersFailsIfPageParameterIsNotOfTypeNumeric(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'getMinScoreFromParameters');

        $parameters = [
            'minScore' => 'something else',
        ];

        try {
            $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$parameters]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'minScore' to be integer|float, got string 'something else'.", $exception->getDetail());
        }
    }

    public function testBuildCombinedQueryBuildsCorrectQuery(): void
    {
        $userId = Uuid::fromString('a743f578-518d-4fdb-855d-28b49e48d671');

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->getUsersGroups(Argument::is($userId))
            ->shouldBeCalledOnce()
            ->willReturn([Uuid::fromString('5e644c3e-00aa-444a-9847-deb675ab9ced')]);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            authProvider: $authProvider->reveal(),
            accessChecker: $accessChecker->reveal()
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'buildCombinedQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [
                'match' => [
                    'description' => [
                        'query' => 'lily',
                    ],
                ],
            ],
            []
        );

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);

        $this->assertEquals(
            [
                '_source' => [
                    '_id',
                ],
                'from' => 0,
                'size' => 25,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'description' => [
                                        'query' => 'lily',
                                    ],
                                ],
                            ],
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'terms' => [
                                                '_groupsWithSearchAccess.keyword' => [
                                                    '5e644c3e-00aa-444a-9847-deb675ab9ced',
                                                ],
                                            ],
                                        ],
                                        [
                                            'term' => [
                                                '_usersWithSearchAccess.keyword' => [
                                                    'value' => 'a743f578-518d-4fdb-855d-28b49e48d671',
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
            $result
        );
    }

    public function testBuildCombinedQueryBuildsCorrectQueryWithMinScore(): void
    {
        $userId = Uuid::fromString('4b320d59-79e3-4f1d-800b-5949490bfd6f');

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->getUsersGroups(Argument::is($userId))
            ->shouldBeCalledOnce()
            ->willReturn([Uuid::fromString('33ba6632-d824-480c-a943-3c098f4c0de7')]);

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            authProvider: $authProvider->reveal(),
            accessChecker: $accessChecker->reveal()
        );
        $method = new ReflectionMethod(ElasticsearchQueryDslMixinSearchStepEventListener::class, 'buildCombinedQuery');

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [
                'match' => [
                    'description' => [
                        'query' => 'lily',
                    ],
                ],
            ],
            [
                'minScore' => 0.75,
            ]
        );

        $result = $method->invokeArgs($elasticsearchQueryDslMixinSearchStepEventListener, [$event]);

        $this->assertEquals(
            [
                '_source' => [
                    '_id',
                ],
                'from' => 0,
                'size' => 25,
                'min_score' => 0.75,
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'match' => [
                                    'description' => [
                                        'query' => 'lily',
                                    ],
                                ],
                            ],
                            [
                                'bool' => [
                                    'should' => [
                                        [
                                            'terms' => [
                                                '_groupsWithSearchAccess.keyword' => [
                                                    '33ba6632-d824-480c-a943-3c098f4c0de7',
                                                ],
                                            ],
                                        ],
                                        [
                                            'term' => [
                                                '_usersWithSearchAccess.keyword' => [
                                                    'value' => '4b320d59-79e3-4f1d-800b-5949490bfd6f',
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
            $result
        );
    }

    public function testOnSearchStepEventReturnsEarlyWhenEventIsOfWrongType(): void
    {
        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener();

        $event = new SearchStepEvent(
            SearchStepType::ELEMENT_HYDRATION,
            null,
            []
        );

        $elasticsearchQueryDslMixinSearchStepEventListener->onSearchStepEvent($event);

        $this->assertFalse($event->isPropagationStopped());
    }

    public function testOnSearchStepEventThrowsIfResultOfElasticsearchQueryIsOfWrongType(): void
    {
        $this->markTestSkipped('Implement test fully once https://github.com/Syndesi/elastic-entity-manager/issues/3 got fixed.');

        $parameterBagInterface = $this->prophesize(ParameterBagInterface::class);
        $parameterBagInterface
            ->get(Argument::is('kernel.environment'))
            ->shouldBeCalledOnce()
            ->willReturn('prod');

        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '500', 'name' => 'internal-server-error']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $server500InternalServerErrorExceptionFactory = new Server500InternalServerErrorExceptionFactory(
            $urlGenerator,
            $parameterBagInterface->reveal(),
            TestLogger::create()
        );

        $elasticClient = $this->prophesize(Client::class);
        $elasticClient
            ->search(Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize('not the correct return value')->reveal());

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager
            ->getClient()
            ->shouldBeCalledOnce()
            ->willReturn($elasticClient->reveal());

        $elasticsearchQueryDslMixinSearchStepEventListener = $this->buildElasticsearchQueryDslMixinSearchStepEventListener(
            elasticEntityManager: $elasticEntityManager->reveal(),
            server500InternalServerErrorExceptionFactory: $server500InternalServerErrorExceptionFactory
        );

        $event = new SearchStepEvent(
            SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN,
            [
                'key' => 'value',
            ],
            []
        );

        try {
            $elasticsearchQueryDslMixinSearchStepEventListener->onSearchStepEvent($event);
            $this->fail('Expected exception to be thrown.');
        } catch (Server500InternalServerErrorException $exception) {
            $this->assertSame('---', $exception->getDetail());
        }
    }

    public function testOnSearchStepEventHandlesEventCorrectly(): void
    {
        $this->markTestSkipped('Implement test fully once https://github.com/Syndesi/elastic-entity-manager/issues/3 got fixed.');
    }
}
