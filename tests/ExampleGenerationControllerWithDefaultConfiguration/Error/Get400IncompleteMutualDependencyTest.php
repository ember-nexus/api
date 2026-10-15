<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Error;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class Get400IncompleteMutualDependencyTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/400/incomplete-mutual-dependency', null);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/error/get-400-incomplete-mutual-dependency/200-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/error/get-400-incomplete-mutual-dependency/200-response-body.txt';
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
