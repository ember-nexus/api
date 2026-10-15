<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Search\ElasticsearchQueryDslMixin;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class QueryStringSearchTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../tests/recordings/search/example/elasticsearch-query-dsl-mixin/query-string-search/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'tests/recordings/search/example/elasticsearch-query-dsl-mixin/query-string-search/response-header.txt';
        $documentationBodyPath = 'tests/recordings/search/example/elasticsearch-query-dsl-mixin/query-string-search/response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertSearchResultInDocumentationIsIdenticalToSearchResultFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
        );
    }
}
