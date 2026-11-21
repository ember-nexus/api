<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Shared helpers of the Elasticsearch search step tests, which run against the plants and taxa of the reference
 * dataset.
 */
abstract class BaseSearchTestCase extends BaseRequestTestCase
{
    protected const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * Query which matches all plants and taxa. `{"match_all": {}}` can not be used, as the empty object of the request
     * is decoded to an empty array, which Elasticsearch does not accept as an object.
     *
     * @return array<string, array<string, string>>
     */
    protected static function matchAll(): array
    {
        return ['exists' => ['field' => 'name']];
    }

    /**
     * @param list<array<string, mixed>> $steps
     */
    protected function runSearch(array $steps): ResponseInterface
    {
        return $this->runPostRequest('/search', self::TOKEN, ['steps' => $steps]);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $parameters
     */
    protected function runElasticsearchStep(array $query, array $parameters = [], bool $hydrate = false): ResponseInterface
    {
        $steps = [
            [
                'type' => 'elasticsearch-query-dsl-mixin',
                'query' => $query,
                'parameters' => $parameters,
            ],
        ];
        if ($hydrate) {
            $steps[] = ['type' => 'element-hydration'];
        }

        return $this->runSearch($steps);
    }

    /**
     * @return list<array<string, mixed>> elements of the result of an Elasticsearch step
     */
    protected function getSearchElements(ResponseInterface $response): array
    {
        $this->assertIsSearchResultResponse($response);

        return $this->getBody($response)['results']['elements'];
    }

    /**
     * @return list<array<string, mixed>> elements of the result of an element hydration step
     */
    protected function getHydratedElements(ResponseInterface $response): array
    {
        $this->assertIsSearchResultResponse($response);

        return $this->getBody($response)['results'];
    }

    /**
     * Asserts that the first search step got rejected with a bad content response, which explains the problem.
     */
    protected function assertSearchStepIsRejected(ResponseInterface $response, string $expectedDetailPart): void
    {
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertStringContainsString($expectedDetailPart, $body['detail']);
        $this->assertSame(0, $body['failedStep']['index']);
        $this->assertSame('elasticsearch-query-dsl-mixin', $body['failedStep']['type']);
    }
}
