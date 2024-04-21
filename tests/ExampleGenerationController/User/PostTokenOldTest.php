<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * @group deprecated
 */
class PostTokenOldTest extends BaseRequestTestCase
{
    private const string EMAIL = 'user@postToken.user.endpoint.localhost.dev';
    private const string PASSWORD = '1234';
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testTokenSuccess(): void
    {
        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => self::PASSWORD,
            ]
        );
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token-old/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token-old/200-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'token',
            ]
        );
    }

    public function testTokenFailure400(): void
    {
        $this->markTestSkipped('Error message changed due to deprecation.');
        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
            ]
        );
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token-old/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token-old/400-response-body.json';
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

    public function testTokenFailure401(): void
    {
        $this->markTestSkipped('Error message changed due to deprecation.');
        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => 'wrongPassword',
            ]
        );
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token-old/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token-old/401-response-body.json';
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
