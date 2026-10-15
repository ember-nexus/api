<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDifferentConfiguration2\Search;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Runs with `expression.timeoutInMilliseconds` set to 100 (the configured minimum), so an expression can be driven
 * past its timeout within a test without waiting on the (much longer, 5 second) default. Must be run via
 * `composer test:example-generation-controller:with-different-configuration-2`, otherwise the default timeout
 * applies and the expression below may complete before timing out.
 */
class PostSearchExpressionTimeoutTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testPostSearchFailure422ExpressionTimeout(): void
    {
        $elements = [];
        for ($i = 0; $i < 3000; ++$i) {
            $elements[] = [
                'id' => 'd747bfe6-0d52-4119-87c6-1fc37ed9395b',
                'score' => $i,
            ];
        }

        // quadratic in the number of elements, so comfortably exceeds the 100ms configured timeout
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'debug' => true,
                'steps' => [
                    [
                        'type' => 'element-hydration',
                        'query' => [
                            'elementIds' => '{{map(elements, {len(filter(elements, {#.score > .score}))})}}',
                        ],
                        'parameters' => [
                            'elements' => $elements,
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 422);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/search/post-search/422-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/search/post-search/422-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response
        );
    }
}
