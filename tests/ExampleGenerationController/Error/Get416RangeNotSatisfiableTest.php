<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Error;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class Get416RangeNotSatisfiableTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runGetRequest('/error/416/range-not-satisfiable', null);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/error/get-416-range-not-satisfiable/200-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/error/get-416-range-not-satisfiable/200-response-body.txt';
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
