<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\Search\CypherPathSubset;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class FindTaggedElementsTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testSearchRequest(): void
    {
        $path = \Safe\realpath(__DIR__.'/../../../../docs/search/example/cypher-path-subset/find-tagged-elements/request-payload.json');

        $data = \Safe\file_get_contents($path);
        $data = json_decode($data, true);
        $response = $this->runPostRequest(
            '/search',
            self::TOKEN,
            $data
        );

        $documentationHeadersPath = 'docs/search/example/cypher-path-subset/find-tagged-elements/response-header.txt';
        $documentationBodyPath = 'docs/search/example/cypher-path-subset/find-tagged-elements/response-body.json';
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
