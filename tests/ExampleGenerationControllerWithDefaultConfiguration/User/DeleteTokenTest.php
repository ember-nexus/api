<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\User;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class DeleteTokenTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    private const string TOKEN = 'secret-token:RIaBS3MoIoQRbu45ES4ZTP';

    public function testDeleteTokenSuccess(): void
    {
        $response = $this->runDeleteRequest('/token', self::TOKEN);
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/user/delete-token/204-response-header.txt';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/user/delete-token/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/user/delete-token/401-response-body.json';
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
