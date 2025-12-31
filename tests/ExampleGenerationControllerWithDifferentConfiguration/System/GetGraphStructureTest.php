<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationControllerWithDifferentConfiguration\System;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetGraphStructureTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGetGraphStructureFailure403(): void
    {
        $response = $this->runGetRequest('/graph-structure', null);
        $documentationHeadersPath = 'docs/api-endpoints/system/get-graph-structure/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/system/get-graph-structure/403-response-body.json';
        $this->assertIsProblemResponse($response, 403);
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true
        );
    }
}
