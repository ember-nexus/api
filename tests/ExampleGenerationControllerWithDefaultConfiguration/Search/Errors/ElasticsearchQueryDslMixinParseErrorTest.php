<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Search\Errors;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Records the explanation of an Elasticsearch parse error. The message of the database is specific to Elasticsearch 8.x; when running against another search engine, exclude this test (differences in `failedStep.diagnostics` are only reported, not failed).
 */
class ElasticsearchQueryDslMixinParseErrorTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-parse-error/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-parse-error/400-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-parse-error/400-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertSearchErrorInDocumentationIsSimilarToSearchErrorFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
        );
    }
}
