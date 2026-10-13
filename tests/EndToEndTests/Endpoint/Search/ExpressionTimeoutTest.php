<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * Exercises the real `ember_nexus.expression.timeoutInMilliseconds` limit (default 5000ms) against the actual
 * Go Expr Lang CLI binary. Kept out of the unit test suite on purpose, since triggering a real timeout here means
 * actually waiting for it.
 */
class ExpressionTimeoutTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testExpressionWithinTimeoutSucceeds(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => 'elasticsearch-query-dsl-mixin',
                        'query' => [
                            'match' => [
                                'description' => [
                                    'query' => '{{"lily"}}',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }

    public function testExpressionExceedingTimeoutReturnsUnprocessableContent(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => 'elasticsearch-query-dsl-mixin',
                        'query' => [
                            'match' => [
                                'description' => [
                                    'query' => '{{sleep(8000)}}',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 422);
        $data = $this->getBody($response);
        $this->assertSame('Query execution timed out after 5000 ms.', $data['detail']);
    }
}
