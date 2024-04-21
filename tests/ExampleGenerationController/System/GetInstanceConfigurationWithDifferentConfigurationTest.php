<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\System;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetInstanceConfigurationWithDifferentConfigurationTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string PATH_TO_CONFIGURATION = __DIR__.'/../../../config/packages/ember_nexus.yaml';

    private string $originalConfiguration = '';

    public function setUp(): void
    {
        parent::setUp();
        $this->originalConfiguration = file_get_contents(self::PATH_TO_CONFIGURATION);
        file_put_contents(
            self::PATH_TO_CONFIGURATION,
            "ember_nexus:\n".
            "  instanceConfiguration:\n".
            "    enabled: false\n"
        );
        exec('cd ../../.. && php bin/console cache:clear');
    }

    public function tearDown(): void
    {
        file_put_contents(self::PATH_TO_CONFIGURATION, $this->originalConfiguration);
        exec('cd ../../.. && php bin/console cache:clear');
        parent::tearDown();
    }

    public function testGetInstanceConfigurationFailure403(): void
    {
        $response = $this->runGetRequest('/instance-configuration', null);
        $documentationHeadersPath = 'docs/api-endpoints/system/get-instance-configuration/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/system/get-instance-configuration/403-response-body.json';
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
