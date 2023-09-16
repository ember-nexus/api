<?php

namespace App\Tests\ExampleGeneration\Error;

use App\Tests\ExampleGeneration\BaseRequestTestCase;

class Get501NotImplementedTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/501/not-implemented', null);
        $documentationHeadersPath = 'docs/api-endpoints/error/get-501-not-implemented/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/error/get-501-not-implemented/200-response-body.txt';
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
