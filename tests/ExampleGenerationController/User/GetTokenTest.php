<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetTokenTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    private const string TOKEN = 'secret-token:7s0vuDVJ1UDH0SErDeaLNe';

    public function testGetTokenSuccess(): void
    {
        $response = $this->runGetRequest('/token', self::TOKEN);
        $this->assertIsNodeResponse($response, 'Token');
        $documentationHeadersPath = 'docs/api-endpoints/user/get-token/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/get-token/200-response-body.json';
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
                'created',
                'updated',
            ]
        );
    }

    public function testGetTokenFailure401(): void
    {
        $response = $this->runGetRequest('/token', 'tokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/user/get-token/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/get-token/401-response-body.json';
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

    public function testGetTokenFailure403(): void
    {
        $response = $this->runGetRequest('/token', null);
        $this->assertIsProblemResponse($response, 403);
        $documentationHeadersPath = 'docs/api-endpoints/user/get-token/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/get-token/403-response-body.json';
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
