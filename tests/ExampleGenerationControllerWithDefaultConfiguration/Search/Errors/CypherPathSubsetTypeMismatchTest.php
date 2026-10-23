<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Search\Errors;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Records the explanation of a Neo4j semantic error (type mismatch) of a syntactically valid query. The message and code are specific to Neo4j 5.x; when running against another cypher database, exclude this test (differences in `failedStep.diagnostics` are only reported, not failed).
 */
class CypherPathSubsetTypeMismatchTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../tests/Recordings/search/example/errors/cypher-path-subset-type-mismatch/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'tests/Recordings/search/example/errors/cypher-path-subset-type-mismatch/400-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/search/example/errors/cypher-path-subset-type-mismatch/400-response-body.json';
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
