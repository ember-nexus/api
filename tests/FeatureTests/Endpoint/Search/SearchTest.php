<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Search;

use App\Tests\FeatureTests\BaseRequestTestCase;

class SearchTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testEmptySearchStepResultsInNoResults(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [],
            ]
        );
        $this->assertIsSearchResultResponse($response);
        $data = $this->getBody($response);
        $this->assertCount(0, $data['results']);
    }

    public function testElasticsearchSearchStepWorks(): void
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
                                    'query' => 'lily',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }

    public function testNamingUndefinedNodeTypesInElasticsearchSearchQueryDoesNotLeadToCrash(): void
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
                                    'query' => 'lily',
                                ],
                            ],
                        ],
                        'parameters' => [
                            'nodeTypes' => [
                                'TypeWhichDoesNotExist',
                            ],
                        ],
                    ],
                    [
                        'type' => 'element-hydration',
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }

    public function testNamingUndefinedRelationTypesInElasticsearchSearchQueryDoesNotLeadToCrash(): void
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
                                    'query' => 'lily',
                                ],
                            ],
                        ],
                        'parameters' => [
                            'relationTypes' => [
                                'TYPE_WHICH_DOES_NOT_EXIST',
                            ],
                        ],
                    ],
                    [
                        'type' => 'element-hydration',
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }

    public function testCypherPathSubsetSearchStepWorks(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => 'cypher-path-subset',
                        'query' => 'MATCH path=((:Plant)-[:HAS_TAG]->(:Tag {name: "blue"})) RETURN path',
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }

    public function testElementHydrationSearchStepWorks(): void
    {
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            [
                'steps' => [
                    [
                        'type' => 'element-hydration',
                        'query' => [
                            'elementIds' => [
                                '258c0dfe-b1d8-4839-beed-d00d1b544a96',
                                '3283fd3d-8083-4170-882a-41b1cd152c55',
                                '8940d70b-5b6f-43b7-bee4-41d073396ff8',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }
}
