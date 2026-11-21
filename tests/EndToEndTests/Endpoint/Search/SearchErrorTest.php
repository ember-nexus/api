<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Failed search steps must be explained to the user, but only with allowlisted data of the database.
 *
 * Assertions are strict on structured fields (status, step, code, position) and loose on message texts, as the
 * texts of the databases differ between versions.
 */
class SearchErrorTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private const string CYPHER_PREFIX = 'MATCH path=((p:Plant)-[:IS_MEMBER_OF]->(t:Taxon)) ';

    /**
     * @param array<string, mixed>|string $query
     */
    private function runFailingStep(string $type, array|string $query, array $parameters = []): ResponseInterface
    {
        return $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => $type,
                        'query' => $query,
                        'parameters' => $parameters,
                    ],
                ],
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getFailedStep(ResponseInterface $response, string $expectedType, string $expectedSource): array
    {
        $body = $this->getBody($response);
        $this->assertArrayHasKey('debug', $body);
        $this->assertArrayHasKey('failedStep', $body);
        $failedStep = $body['failedStep'];
        $this->assertSame(0, $failedStep['index']);
        $this->assertSame($expectedType, $failedStep['type']);
        $this->assertSame($expectedSource, $failedStep['source']);
        $this->assertArrayHasKey('debug', $failedStep);
        $this->assertArrayHasKey('input', $failedStep['debug']);

        return $failedStep;
    }

    public function testNestedQueryKeyInElasticsearchQueryIsExplained(): void
    {
        // a single `query` property is the extended query definition, wrapping the query twice is a mistake
        $response = $this->runFailingStep(
            'elasticsearch-query-dsl-mixin',
            ['query' => ['query' => ['match' => ['description' => ['query' => 'lily']]]]]
        );
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertSame([], $body['debug']);

        $failedStep = $this->getFailedStep($response, 'elasticsearch-query-dsl-mixin', 'elasticsearch');
        $this->assertSame('invalid-query', $failedStep['code']);
        $this->assertSame('x_content_parse_exception', $failedStep['diagnostics']['code']);
        $this->assertSame(400, $failedStep['diagnostics']['status']);
        $this->assertFalse($failedStep['diagnostics']['validation']['valid']);
        $this->assertStringContainsString('unknown query [query]', $failedStep['diagnostics']['message']);
        // the whole combined query is returned, same as in the debug section of successful responses
        $this->assertArrayHasKey('combinedQuery', $failedStep['debug']);
        $this->assertSame(
            ['query' => ['match' => ['description' => ['query' => 'lily']]]],
            $failedStep['debug']['combinedQuery']['query']['bool']['must'][0]
        );
        $this->assertSame(
            ['query' => ['match' => ['description' => ['query' => 'lily']]]],
            $failedStep['debug']['input']['query']['query']
        );
    }

    public function testUnknownQueryTypeInElasticsearchQueryIsExplained(): void
    {
        $response = $this->runFailingStep('elasticsearch-query-dsl-mixin', ['does_not_exist' => ['description' => 'lily']]);
        $this->assertIsProblemResponse($response, 400);
        $failedStep = $this->getFailedStep($response, 'elasticsearch-query-dsl-mixin', 'elasticsearch');
        $this->assertStringContainsString('does_not_exist', $failedStep['diagnostics']['message']);
    }

    public function testErrorResponseContainsDebugDataOfPreviousStepsButNotOfTheFailedStep(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => 'elasticsearch-query-dsl-mixin',
                        'query' => ['match' => ['description' => ['query' => 'lily']]],
                    ],
                    [
                        'type' => 'cypher-path-subset',
                        'query' => self::CYPHER_PREFIX.'WHERE t.name IN 5 RETURN path',
                    ],
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertCount(1, $body['debug']);
        $this->assertSame('elasticsearch-query-dsl-mixin', $body['debug'][0]['input']['type']);
        $this->assertSame(1, $body['failedStep']['index']);
        $this->assertSame('cypher-path-subset', $body['failedStep']['type']);
        $this->assertSame('cypher-path-subset', $body['failedStep']['debug']['input']['type']);
    }

    public function testCypherTypeMismatchIsExplainedWithPosition(): void
    {
        $query = self::CYPHER_PREFIX.'WHERE t.name IN 5 RETURN path';
        $response = $this->runFailingStep('cypher-path-subset', $query);
        $this->assertIsProblemResponse($response, 400);

        $failedStep = $this->getFailedStep($response, 'cypher-path-subset', 'neo4j');
        $this->assertSame('invalid-query', $failedStep['code']);
        $this->assertStringStartsWith('Neo.ClientError.Statement.', $failedStep['diagnostics']['code']);
        $this->assertStringContainsString('Type mismatch', $failedStep['diagnostics']['message']);
        // the position refers to the query of the user, not to the explained statement
        $position = $failedStep['diagnostics']['position'];
        $this->assertSame(1, $position['line']);
        $this->assertSame(strpos($query, '5'), $position['offset']);
        $this->assertSame($position['offset'] + 1, $position['column']);
    }

    public function testCypherUndefinedVariableIsExplained(): void
    {
        $response = $this->runFailingStep('cypher-path-subset', self::CYPHER_PREFIX.'WHERE unknownVariable.name = "a" RETURN path');
        $this->assertIsProblemResponse($response, 400);
        $failedStep = $this->getFailedStep($response, 'cypher-path-subset', 'neo4j');
        $this->assertStringContainsString('unknownVariable', $failedStep['diagnostics']['message']);
    }

    public function testCypherMissingParameterIsExplainedWithoutDatabaseMessage(): void
    {
        $response = $this->runFailingStep('cypher-path-subset', self::CYPHER_PREFIX.'WHERE t.name IN $missingParameter RETURN path');
        $this->assertIsProblemResponse($response, 400);
        $failedStep = $this->getFailedStep($response, 'cypher-path-subset', 'neo4j');
        $this->assertSame('query-execution-failed', $failedStep['code']);
        $this->assertSame('Neo.ClientError.Statement.ParameterMissing', $failedStep['diagnostics']['code']);
        $this->assertArrayNotHasKey('message', $failedStep['diagnostics']);
    }

    public function testCypherQueryPlanIsNeverReturned(): void
    {
        $response = $this->runFailingStep('cypher-path-subset', self::CYPHER_PREFIX.'WHERE t.name IN $missingParameter RETURN path');
        $diagnostics = $this->getBody($response)['failedStep']['diagnostics'];
        $this->assertArrayNotHasKey('plan', $diagnostics);
        $rawBody = (string) $response->getBody();
        foreach (['EstimatedRows', 'estimatedRows', 'operatorType', 'ProduceResults'] as $planMarker) {
            $this->assertStringNotContainsString($planMarker, $rawBody);
        }
    }

    public function testExpressionErrorInElasticsearchQueryIsScopedToExpressionError(): void
    {
        $response = $this->runFailingStep('elasticsearch-query-dsl-mixin', ['match' => ['description' => ['query' => '{{ 1 + }}']]]);
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertSame('expr-lang', $body['expressionError']['source']);
        $this->assertSame('invalid-query', $body['expressionError']['code']);
        $this->assertSame('compile error', $body['expressionError']['diagnostics']['kind']);
        $this->assertSame(1, $body['expressionError']['diagnostics']['line']);
        $this->assertSame(0, $body['failedStep']['index']);
    }
}
