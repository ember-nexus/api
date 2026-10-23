<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\QueryExplanation\Elasticsearch\ElasticsearchQueryExplanationService;
use App\Service\QueryExplanation\Neo4j\Neo4jQueryExplanationService;
use App\Service\SearchStepFailureService;
use App\Type\QueryExplanation;
use App\Type\SearchStepType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(SearchStepFailureService::class)]
class SearchStepFailureServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $elasticsearchService;
    private ObjectProphecy $neo4jService;

    private function getService(bool $explainFailures = true): SearchStepFailureService
    {
        $emberNexusConfiguration = new EmberNexusConfiguration();
        $emberNexusConfiguration->setSearchExplainFailures($explainFailures);
        $this->elasticsearchService = $this->prophesize(ElasticsearchQueryExplanationService::class);
        $this->neo4jService = $this->prophesize(Neo4jQueryExplanationService::class);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('https://mock.dev/123');

        return new SearchStepFailureService(
            $this->elasticsearchService->reveal(),
            $this->neo4jService->reveal(),
            $emberNexusConfiguration,
            $this->prophesize(LoggerInterface::class)->reveal(),
            new Client400BadContentExceptionFactory($urlGenerator->reveal()),
        );
    }

    private function esEvent(array $debug = []): SearchStepEvent
    {
        $event = new SearchStepEvent(SearchStepType::ELASTICSEARCH_QUERY_DSL_MIXIN, ['match_all' => []], []);
        foreach ($debug as $key => $value) {
            $event->addDebugData($key, $value);
        }

        return $event;
    }

    public function testProblemJsonExceptionPassesThroughWithDebugAndFailedStep(): void
    {
        $failure = new Client400BadContentException('type', detail: 'bad');
        $event = new SearchStepEvent(SearchStepType::ELEMENT_HYDRATION, null, []);

        $exception = $this->getService()->createException(2, $event, [['a' => 1]], $failure);

        $this->assertSame($failure, $exception);
        $properties = $exception->getAdditionalProperties();
        $this->assertSame([['a' => 1]], $properties['debug']);
        $this->assertSame(2, $properties['failedStep']['index']);
        $this->assertSame('element-hydration', $properties['failedStep']['type']);
        $this->assertSame($event->getDebugData(), $properties['failedStep']['debug']);
        $this->assertArrayNotHasKey('diagnostics', $properties['failedStep']);
    }

    public function testElasticsearchStepExtractsUserQueryAndIndices(): void
    {
        $service = $this->getService();
        $failure = new Exception('raw');
        $this->elasticsearchService->explain($failure, ['match' => ['a' => 'b']], 'node_plant')
            ->shouldBeCalledOnce()
            ->willReturn(new QueryExplanation('elasticsearch', QueryExplanation::CODE_INVALID_QUERY, true, ['message' => 'm'], true));
        $event = $this->esEvent([
            'indices' => 'node_plant',
            'combinedQuery' => ['query' => ['bool' => ['must' => [['match' => ['a' => 'other']], ['bool' => []]]]]],
        ]);
        $event->setResolvedQuery(['match' => ['a' => 'b']]);

        $exception = $service->createException(0, $event, [], $failure);

        $this->assertInstanceOf(Client400BadContentException::class, $exception);
        $this->assertSame('Search step 1 (type elasticsearch-query-dsl-mixin) was rejected by elasticsearch, see failedStep.diagnostics for the reason.', $exception->getDetail());
        $this->assertSame($failure, $exception->getPrevious());
        $failedStep = $exception->getAdditionalProperties()['failedStep'];
        $this->assertSame('elasticsearch', $failedStep['source']);
        $this->assertSame('invalid-query', $failedStep['code']);
        $this->assertSame(['message' => 'm'], $failedStep['diagnostics']);
        $this->assertArrayHasKey('combinedQuery', $failedStep['debug']);
    }

    public function testElasticsearchStepFallsBackToStarIndices(): void
    {
        $service = $this->getService();
        $failure = new Exception('raw');
        $this->elasticsearchService->explain($failure, ['match' => ['a' => 'b']], '*')->shouldBeCalledOnce()->willReturn(null);
        $event = $this->esEvent();
        $event->setResolvedQuery(['match' => ['a' => 'b']]);

        $exception = $service->createException(0, $event, [], $failure);

        $this->assertSame(400, $exception->getStatus());
    }

    public function testElasticsearchStepWithoutResolvedQueryIsNotExplained(): void
    {
        $service = $this->getService();
        $this->elasticsearchService->explain(Argument::cetera())->shouldNotBeCalled();

        $exception = $service->createException(0, $this->esEvent(), [], new Exception('raw'));

        $this->assertSame(400, $exception->getStatus());
        $this->assertArrayNotHasKey('source', $exception->getAdditionalProperties()['failedStep']);
    }

    public function testFailureIsShort400WithoutDebugDataIfExplainingFailuresIsDisabled(): void
    {
        $service = $this->getService(false);
        $this->elasticsearchService->explain(Argument::cetera())->shouldNotBeCalled();
        $this->neo4jService->explain(Argument::cetera())->shouldNotBeCalled();
        $failure = new Exception('secret');
        $event = $this->esEvent(['combinedQuery' => ['x' => 'y']]);

        $exception = $service->createException(1, $event, [['a' => 1]], $failure);

        $this->assertInstanceOf(Client400BadContentException::class, $exception);
        $this->assertSame('Search step 2 (type elasticsearch-query-dsl-mixin) failed.', $exception->getDetail());
        $this->assertSame([], $exception->getAdditionalProperties());
        $this->assertSame($failure, $exception->getPrevious());
    }

    public function testProblemJsonExceptionPassesThroughUnchangedIfExplainingFailuresIsDisabled(): void
    {
        $failure = new Client400BadContentException('type', detail: 'bad');
        $event = new SearchStepEvent(SearchStepType::ELEMENT_HYDRATION, null, []);

        $exception = $this->getService(false)->createException(2, $event, [['a' => 1]], $failure);

        $this->assertSame($failure, $exception);
        $this->assertSame([], $exception->getAdditionalProperties());
    }

    public function testUnexplainedFailureIsGeneric400WithoutRawMessage(): void
    {
        $service = $this->getService();
        $this->elasticsearchService->explain(Argument::cetera())->willReturn(null);
        $event = $this->esEvent();
        $event->setResolvedQuery(['match_all' => []]);

        $exception = $service->createException(1, $event, [['x' => 1]], new Exception('secret document data'));

        $this->assertSame(400, $exception->getStatus());
        $this->assertStringContainsString('Search step 2 (type elasticsearch-query-dsl-mixin) failed. The reason can not be shown safely', $exception->getDetail());
        $this->assertStringNotContainsString('secret', $exception->getDetail());
        $properties = $exception->getAdditionalProperties();
        $this->assertSame([['x' => 1]], $properties['debug']);
        $this->assertSame(1, $properties['failedStep']['index']);
        $this->assertArrayNotHasKey('source', $properties['failedStep']);
    }

    public function testCypherStepIsExplainedByNeo4jService(): void
    {
        $service = $this->getService();
        $failure = new Exception('raw');
        $event = new SearchStepEvent(SearchStepType::CYPHER_PATH_SUBSET, 'MATCH q', ['p' => 1]);
        $this->neo4jService->explain($failure, 'MATCH q', ['p' => 1])
            ->shouldBeCalledOnce()
            ->willReturn(new QueryExplanation('neo4j', QueryExplanation::CODE_EXECUTION_FAILED, true, ['code' => 'c']));

        $exception = $service->createException(0, $event, [], $failure);

        $this->assertSame(400, $exception->getStatus());
        $this->assertStringContainsString('The database message is withheld', $exception->getDetail());
        $this->assertStringContainsString('failed while neo4j was running it', $exception->getDetail());
        $this->assertSame(['code' => 'c'], $exception->getAdditionalProperties()['failedStep']['diagnostics']);
    }

    public function testCypherStepWithNonStringQueryIsNotExplained(): void
    {
        $service = $this->getService();
        $this->neo4jService->explain(Argument::cetera())->shouldNotBeCalled();
        $event = new SearchStepEvent(SearchStepType::CYPHER_PATH_SUBSET, ['not' => 'string'], []);

        $exception = $service->createException(0, $event, [], new Exception('raw'));

        $this->assertSame(400, $exception->getStatus());
    }

    public function testEmptyDatabaseDataIsOmitted(): void
    {
        $service = $this->getService();
        $event = new SearchStepEvent(SearchStepType::CYPHER_PATH_SUBSET, 'q', []);
        $this->neo4jService->explain(Argument::cetera())
            ->willReturn(new QueryExplanation('neo4j', QueryExplanation::CODE_EXECUTION_FAILED, true));

        $exception = $service->createException(0, $event, [], new Exception('raw'));

        $this->assertArrayNotHasKey('diagnostics', $exception->getAdditionalProperties()['failedStep']);
    }

    public function testServerSideDatabaseFailureIsExplainedAs400WithWithheldMessage(): void
    {
        $service = $this->getService();
        $event = new SearchStepEvent(SearchStepType::CYPHER_PATH_SUBSET, 'q', []);
        $this->neo4jService->explain(Argument::cetera())
            ->willReturn(new QueryExplanation('neo4j', QueryExplanation::CODE_EXECUTION_FAILED, false, ['code' => 'Neo.TransientError.X']));

        $exception = $service->createException(0, $event, [], new Exception('raw'));

        $this->assertSame(400, $exception->getStatus());
        $this->assertStringContainsString('failed while neo4j was running it', $exception->getDetail());
        $this->assertSame('Neo.TransientError.X', $exception->getAdditionalProperties()['failedStep']['diagnostics']['code']);
    }

    public function testElementHydrationFailureIsNotExplained(): void
    {
        $service = $this->getService();
        $event = new SearchStepEvent(SearchStepType::ELEMENT_HYDRATION, null, []);

        $exception = $service->createException(0, $event, [], new Exception('raw'));

        $this->assertSame(400, $exception->getStatus());
        $this->assertStringContainsString('(type element-hydration)', $exception->getDetail());
    }
}
