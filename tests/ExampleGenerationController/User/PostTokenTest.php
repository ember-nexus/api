<?php

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostTokenTest extends BaseRequestTestCase
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
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token/200-response-body.json';
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
        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
            ]
        );
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token/400-response-body.json';
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
        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => 'wrongPassword',
            ]
        );
        $documentationHeadersPath = 'docs/api-endpoints/user/post-token/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-token/401-response-body.json';
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
