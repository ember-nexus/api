<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\Search\ElasticsearchQueryDslMixin;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class ParameterizedElasticsearchQueryDSLMixinSearchTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../docs/search/example/elasticsearch-query-dsl-mixin/parameterized-elasticsearch-query-dsl-mixin-search/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'docs/search/example/elasticsearch-query-dsl-mixin/parameterized-elasticsearch-query-dsl-mixin-search/response-header.txt';
        $documentationBodyPath = 'docs/search/example/elasticsearch-query-dsl-mixin/parameterized-elasticsearch-query-dsl-mixin-search/response-body.json';
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
