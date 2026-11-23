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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/system/get-graph-structure/200-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/system/get-graph-structure/200-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response,
            // the length depends on the node types which tests running earlier created, see below
            ['Content-Length']
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'version',
                // created by earlier tests which upload files, depending on the order of the tests it exists or not
                '"Upload"',
            ]
        );
    }
}
