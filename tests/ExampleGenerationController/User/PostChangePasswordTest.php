<?php

namespace App\Tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostChangePasswordTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    public function testChangePasswordSuccess(): void
    {
        $response = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'data' => [
                    'email' => 'user@changePassword.user.endpoint.localhost.dev',
                ],
            ]
        );
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testChangePasswordError400(): void
    {
        $response = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'NotActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'data' => [
                    'email' => 'user@changePassword.user.endpoint.localhost.dev',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password/400-response-body.json';
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

    public function testChangePasswordError401(): void
    {
        $response = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'data' => [
                    'email' => 'this-user-does-not-exist@localhost.dev',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password/401-response-body.json';
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

    public function testChangePasswordError403(): void
    {
        $response = $this->runPostRequest(
            '/change-password',
            null,
            [
                'type' => 'ActionChangePassword',
                'currentPassword' => '1234',
                'newPassword' => 'abcd',
                'data' => [
                    'email' => 'anonymous-user@localhost.dev',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 403);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password/403-response-body.json';
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
