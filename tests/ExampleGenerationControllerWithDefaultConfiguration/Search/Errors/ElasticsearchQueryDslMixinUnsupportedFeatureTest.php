<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Search\Errors;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Records the response for an Elasticsearch query which uses an unsupported feature (here: scripts). The query is rejected by Ember Nexus itself, so the response does not contain data of the database.
 */
class ElasticsearchQueryDslMixinUnsupportedFeatureTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-unsupported-feature/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-unsupported-feature/400-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/search/example/errors/elasticsearch-query-dsl-mixin-unsupported-feature/400-response-body.json';
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
