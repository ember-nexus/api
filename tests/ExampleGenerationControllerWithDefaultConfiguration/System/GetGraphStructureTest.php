<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\System;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class GetGraphStructureTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGraphStructureSuccess(): void
    {
        $response = $this->runGetRequest('/graph-structure', null);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/system/get-graph-structure/200-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/system/get-graph-structure/200-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'version',
            ]
        );
    }
}
