<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * @group deprecated
 */
class PostChangePasswordOldTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

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
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password-old/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testChangePasswordError400(): void
    {
        $this->markTestSkipped('Error message changed due to deprecation.');
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
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password-old/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password-old/400-response-body.json';
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
        $this->markTestSkipped('Error message changed due to deprecation.');
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
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password-old/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password-old/401-response-body.json';
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
        $this->markTestSkipped('Error message changed due to deprecation.');
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
        $documentationHeadersPath = 'docs/api-endpoints/user/post-change-password-old/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-change-password-old/403-response-body.json';
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
