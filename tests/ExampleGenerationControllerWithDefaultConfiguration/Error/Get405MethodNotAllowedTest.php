<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Error;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class Get405MethodNotAllowedTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/405/method-not-allowed', null);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/error/get-405-method-not-allowed/200-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/error/get-405-method-not-allowed/200-response-body.txt';
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
