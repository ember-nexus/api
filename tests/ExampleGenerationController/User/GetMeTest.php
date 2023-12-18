<?php

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetMeTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGetMeSuccess(): void
    {
        $response = $this->runGetRequest('/me', null);
        $this->assertIsNodeResponse($response, 'User');
        $documentationHeadersPath = 'docs/api-endpoints/user/get-me/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/get-me/200-response-body.json';
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

    public function testGetMeFailure401(): void
    {
        $response = $this->runGetRequest('/me', 'tokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/user/get-me/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/get-me/401-response-body.json';
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
