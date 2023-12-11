<?php

namespace App\tests\ExampleGenerationController\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class DeleteTokenTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';

    private const TOKEN = 'secret-token:RIaBS3MoIoQRbu45ES4ZTP';

    public function testDeleteTokenSuccess(): void
    {
        $response = $this->runDeleteRequest('/token', self::TOKEN);
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/user/delete-token/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteTokenFailure401(): void
    {
        $response = $this->runDeleteRequest('/token', 'tokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/user/delete-token/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/delete-token/401-response-body.json';
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
