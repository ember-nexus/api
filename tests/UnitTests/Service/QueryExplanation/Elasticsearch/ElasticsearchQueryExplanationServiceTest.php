<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\QueryExplanation\Elasticsearch;

use App\Service\QueryExplanation\Elasticsearch\ElasticsearchQueryExplanationService;
use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Exception;
use GuzzleHttp\Psr7\Response;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

#[Small]
#[CoversClass(ElasticsearchQueryExplanationService::class)]
class ElasticsearchQueryExplanationServiceTest extends TestCase
{
    use ProphecyTrait;

    private function psrResponse(int $status, string $body): Response
    {
        return new Response($status, ['X-Elastic-Product' => 'Elasticsearch', 'Content-Type' => 'application/json'], $body);
    }

    private function clientException(int $status, string $body): ClientResponseException
    {
        $exception = new ClientResponseException('failed', $status);
        $exception->setResponse($this->psrResponse($status, $body));

        return $exception;
    }

    private function elasticsearchResponse(array $data): Response
    {
        return $this->psrResponse(200, json_encode($data));
    }

    /**
     * @param callable|null $validate receives the decoded validateQuery request and returns a PSR response or throws; null means validation must not be called
     */
    private function getService(?callable $validate = null, ?array &$request = null): ElasticsearchQueryExplanationService
    {
        $httpClient = new class($validate, $request) implements ClientInterface {
            /** @var callable|null */
            private $validate;

            public function __construct(?callable $validate, private ?array &$request)
            {
                $this->validate = $validate;
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                if (null === $this->validate) {
                    throw new LogicException('validation must not be called');
                }
                $this->request = [
                    'path' => $request->getUri()->getPath(),
                    'query' => $request->getUri()->getQuery(),
                    'body' => json_decode((string) $request->getBody(), true),
                ];

                return ($this->validate)($this->request);
            }
        };
        $client = ClientBuilder::create()->setHttpClient($httpClient)->setHosts(['http://localhost:9200'])->build();
        $entityManager = $this->prophesize(ElasticEntityManager::class);
        $entityManager->getClient()->willReturn($client);

        return new ElasticsearchQueryExplanationService($entityManager->reveal(), new QueryExplanationPayloadService());
    }

    private const string BAD_BODY = '{"error":{"root_cause":[{"type":"parsing_exception","reason":"secret"},{"type":"parsing_exception"},{"type":"weird"},"x"],"type":"x_content_parse_exception","reason":"secret"},"status":400}';

    public function testNonElasticsearchFailureIsNotExplained(): void
    {
        $this->assertNull($this->getService()->explain(new Exception('x'), []));
    }

    public function testElasticsearchFailureWithoutResponseIsNotAUserError(): void
    {
        $failure = new class('x') extends Exception implements ElasticsearchException {
        };

        $explanation = $this->getService()->explain($failure, ['match_all' => []]);

        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertFalse($explanation->isUserError);
        $this->assertSame([], $explanation->diagnostics);
    }

    public function testInvalidQueryIsExplainedByValidation(): void
    {
        $params = null;
        $service = $this->getService(function () {
            return $this->elasticsearchResponse([
                'valid' => false,
                'error' => 'org.elasticsearch.common.ParsingException: unknown query [query]; org.elasticsearch.xcontent.NamedObjectNotFoundException: [1:19] unknown field [query]',
            ]);
        }, $params);

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), ['query' => ['x' => 1]], 'node_plant');

        $this->assertSame('/node_plant/_validate/query', $params['path']);
        $this->assertStringContainsString('explain=true', $params['query']);
        $this->assertStringNotContainsString('rewrite', $params['query']);
        $this->assertSame(['query' => ['query' => ['x' => 1]]], $params['body']);
        $this->assertSame('elasticsearch', $explanation->source);
        $this->assertSame(QueryExplanation::CODE_INVALID_QUERY, $explanation->code);
        $this->assertTrue($explanation->isUserError);
        $this->assertTrue($explanation->isMessageSafe);
        $this->assertSame(
            [
                'code' => 'x_content_parse_exception',
                'message' => 'unknown query [query]; org.elasticsearch.xcontent.NamedObjectNotFoundException: [1:19] unknown field [query]',
                'status' => 400,
                'rootCauseCodes' => ['parsing_exception'],
                'validation' => ['valid' => false, 'exception' => 'org.elasticsearch.common.ParsingException'],
            ],
            $explanation->diagnostics
        );
        $this->assertStringNotContainsString('secret', json_encode($explanation->diagnostics));
    }

    public function testValidationErrorOfOtherExceptionClassIsWithheld(): void
    {
        $service = $this->getService(fn () => $this->elasticsearchResponse([
            'valid' => false,
            'error' => 'java.lang.NullPointerException: contains secret data',
        ]));

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), []);

        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertFalse($explanation->isMessageSafe);
        $this->assertTrue($explanation->isUserError);
        $this->assertArrayNotHasKey('message', $explanation->diagnostics);
        $this->assertStringNotContainsString('secret', json_encode($explanation->diagnostics));
    }

    public function testValidationErrorWithoutSeparatorIsWithheld(): void
    {
        $service = $this->getService(fn () => $this->elasticsearchResponse(['valid' => false, 'error' => 'nonsense']));

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), []);

        $this->assertFalse($explanation->isMessageSafe);
    }

    public function testExplanationsOfValidQueriesAreNeverReturned(): void
    {
        $service = $this->getService(fn () => $this->elasticsearchResponse([
            'valid' => true,
            'explanations' => [['index' => 'node_plant', 'valid' => true, 'explanation' => 'description:secretterm']],
        ]));

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), []);

        $this->assertFalse($explanation->isMessageSafe);
        $this->assertSame(QueryExplanation::CODE_EXECUTION_FAILED, $explanation->code);
        $this->assertStringNotContainsString('secretterm', json_encode($explanation->diagnostics));
        $this->assertStringNotContainsString('explanation', json_encode($explanation->diagnostics));
    }

    public function testPerIndexValidationErrorIsUsedAsFallback(): void
    {
        $service = $this->getService(fn () => $this->elasticsearchResponse([
            'valid' => false,
            'explanations' => [
                'not-an-array',
                ['index' => 'a', 'valid' => true],
                ['index' => 'b', 'valid' => false, 'error' => 'org.elasticsearch.index.query.QueryShardException: failed to create query'],
            ],
        ]));

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), []);

        $this->assertTrue($explanation->isMessageSafe);
        $this->assertSame('failed to create query', $explanation->diagnostics['message']);
    }

    public function testInvalidWithoutAnyErrorIsWithheld(): void
    {
        $service = $this->getService(fn () => $this->elasticsearchResponse(['valid' => false, 'explanations' => 'x']));

        $this->assertFalse($service->explain($this->clientException(400, self::BAD_BODY), [])->isMessageSafe);
    }

    public function testFailingValidationIsWithheld(): void
    {
        $service = $this->getService(fn () => throw new RuntimeException('down'));

        $explanation = $service->explain($this->clientException(400, self::BAD_BODY), []);

        $this->assertFalse($explanation->isMessageSafe);
        $this->assertSame('x_content_parse_exception', $explanation->diagnostics['code']);
    }

    public function testValidationIsSkippedWithoutUserQuery(): void
    {
        $explanation = $this->getService()->explain($this->clientException(400, self::BAD_BODY), null);

        $this->assertTrue($explanation->isUserError);
        $this->assertFalse($explanation->isMessageSafe);
    }

    public function testNon400ClientErrorIsNotAUserErrorAndSkipsValidation(): void
    {
        $explanation = $this->getService()->explain(
            $this->clientException(429, '{"error":{"type":"circuit_breaking_exception","root_cause":[]},"status":429}'),
            []
        );

        $this->assertFalse($explanation->isUserError);
        $this->assertSame('circuit_breaking_exception', $explanation->diagnostics['code']);
        $this->assertSame(429, $explanation->diagnostics['status']);
    }

    public function testServerErrorIsNotAUserError(): void
    {
        $failure = new ServerResponseException('failed', 500);
        $failure->setResponse($this->psrResponse(500, '{"error":{"type":"unsafe_type_with_data","root_cause":"x"}}'));

        $explanation = $this->getService()->explain($failure, []);

        $this->assertFalse($explanation->isUserError);
        $this->assertSame(['status' => 500, 'rootCauseCodes' => []], $explanation->diagnostics);
    }

    public function testUnparsableBodiesAreHandled(): void
    {
        $service = $this->getService();

        $notJson = $service->explain($this->clientException(404, 'not json'), null);
        $noError = $service->explain($this->clientException(404, '{"status":404}'), null);

        $this->assertSame(['status' => 404, 'rootCauseCodes' => []], $notJson->diagnostics);
        $this->assertSame(['status' => 404, 'rootCauseCodes' => []], $noError->diagnostics);
    }

    public function testMissingResponseIsHandled(): void
    {
        // getResponse() has a non-nullable return type and throws if no response was set
        $explanation = $this->getService()->explain(new ClientResponseException('x', 400), null);

        $this->assertSame(['rootCauseCodes' => []], $explanation->diagnostics);
        $this->assertFalse($explanation->isUserError);
    }
}
