<?php

namespace App\tests\ExampleGenerationController\Error;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class Get500InternalServerErrorTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/500/internal-server-error', null);
        $documentationHeadersPath = 'docs/api-endpoints/error/get-500-internal-server-error/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/error/get-500-internal-server-error/200-response-body.txt';
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
