<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDifferentConfiguration1\System;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetInstanceConfigurationTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGetInstanceConfigurationFailure403(): void
    {
        $response = $this->runGetRequest('/instance-configuration', null);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/system/get-instance-configuration/403-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/system/get-instance-configuration/403-response-body.json';
        $this->assertIsProblemResponse($response, 403);
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true
        );
    }
}
