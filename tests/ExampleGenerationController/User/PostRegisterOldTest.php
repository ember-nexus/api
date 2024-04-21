<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * @group deprecated
 */
class PostRegisterOldTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterSuccess(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'data' => [
                    'email' => 'test@localhost.dev',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-register-old/201-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testRegisterError400(): void
    {
        $this->markTestSkipped('Error message changed due to deprecation.');
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'NotAUser',
                'password' => '1234',
                'data' => [
                    'email' => 'test2@localhost.dev',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-register-old/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-register-old/400-response-body.json';
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
