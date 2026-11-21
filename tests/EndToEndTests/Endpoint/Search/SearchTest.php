<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use App\Tests\EndToEndTests\BaseRequestTestCase;

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
                                '02IiNr0GOoKiPMLPDlPbKrlR4vnBQsvSBSkU5',
                                '02IiNr0GOoKiPMLVYC87gam9dbmx8E6khf0g8',
                                '02IiNr0GOoKiPMMBnI2aIF8Qd4GH33FIAozo7',
                            ],
                        ],
                    ],
                ],
            ]
        );
        $this->assertIsSearchResultResponse($response);
    }
}
