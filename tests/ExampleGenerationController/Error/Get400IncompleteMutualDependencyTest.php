<?php

namespace App\Tests\ExampleGenerationController\Error;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class Get400IncompleteMutualDependencyTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/400/incomplete-mutual-dependency', null);
        $documentationHeadersPath = 'docs/api-endpoints/error/get-400-incomplete-mutual-dependency/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/error/get-400-incomplete-mutual-dependency/200-response-body.txt';
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