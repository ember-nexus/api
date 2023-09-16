<?php

namespace App\Tests\ExampleGeneration\Error;

use App\Tests\ExampleGeneration\BaseRequestTestCase;

class Get400ReservedIdentifierTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/400/reserved-identifier', null);
        $documentationHeadersPath = 'docs/api-endpoints/error/get-400-reserved-identifier/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/error/get-400-reserved-identifier/200-response-body.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            false
        );
    }
}
