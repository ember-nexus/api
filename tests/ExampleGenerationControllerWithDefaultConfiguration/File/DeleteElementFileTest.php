<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\File;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Every test creates its own "Data" element (with a fixed id, so responses which echo it back stay identical
 * across runs) before exercising the endpoint, so none of them depend on, or interfere with, each other.
 */
class DeleteElementFileTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testDeleteElementFileSuccess204(): void
    {
        $elementId = '02IiNr0G8viOVKkDTDZdpTYb3cANfyAOQQP1Q';
        $this->createDocumentedElement($elementId, 'delete-element-file-204', self::TOKEN);

        $filePath = __DIR__.'/../Asset/example-delete-element-file-204.bin';
        $this->generateDeterministicFile(99999999, 2048, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $uploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($uploadResponse, false);
        unlink($filePath);

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertNoContentResponse($response);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/delete-element-file/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteElementFileFailure401(): void
    {
        $elementId = '02IiNr0G8viOVKkIrDhak1WB88bdj03TJtmiT';
        $this->createDocumentedElement($elementId, 'delete-element-file-401', self::TOKEN);

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementId), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/delete-element-file/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/delete-element-file/401-response-body.json';
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

    public function testDeleteElementFileFailure404(): void
    {
        $elementWhichDoesNotExist = '02IiNr0G8viOVKkkt30R04iI2cjxQKZ9bnCYA';

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementWhichDoesNotExist), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/delete-element-file/404-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/delete-element-file/404-response-body.json';
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
