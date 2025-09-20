<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\System;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetGraphStructureTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGraphStructureSuccess(): void
    {
        $response = $this->runGetRequest('/graph-structure', null);
        $documentationHeadersPath = 'docs/api-endpoints/system/get-graph-structure/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/system/get-graph-structure/200-response-body.json';
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
