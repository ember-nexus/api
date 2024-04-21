<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\Error;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class Get401UnauthorizedTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/401/unauthorized', null);
        $documentationHeadersPath = 'docs/api-endpoints/error/get-401-unauthorized/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/error/get-401-unauthorized/200-response-body.txt';
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
