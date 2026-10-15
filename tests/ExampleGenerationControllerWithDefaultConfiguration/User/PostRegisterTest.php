<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\User;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class PostRegisterTest extends BaseRequestTestCase
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
                'uniqueUserIdentifier' => 'test@localhost.dev',
            ]
        );
        $this->assertIsCreatedResponse($response);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/user/post-register/201-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testRegisterError400ReservedIdentifier(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user@postToken.user.endpoint.localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/user/post-register/400-reserved-identifier-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/user/post-register/400-reserved-identifier-response-body.json';
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

    public function testRegisterError400(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'NotAUser',
                'password' => '1234',
                'uniqueUserIdentifier' => 'test2@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/user/post-register/400-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/user/post-register/400-response-body.json';
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
