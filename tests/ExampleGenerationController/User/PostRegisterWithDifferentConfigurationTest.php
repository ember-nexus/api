<?php

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostRegisterWithDifferentConfigurationTest extends BaseRequestTestCase
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
            "  register:\n".
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

    public function testRegisterError403(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'data' => [
                    'email' => 'test2@localhost.dev',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 403);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-register/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-register/403-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response
        );
    }
}
