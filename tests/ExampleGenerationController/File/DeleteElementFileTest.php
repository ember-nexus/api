<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\File;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own "Data" element (with a fixed id, so responses which echo it back stay identical
 * across runs) before exercising the endpoint, so none of them depend on, or interfere with, each other.
 */
class DeleteElementFileTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createElement(string $id, string $name): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'id' => $id,
                'type' => 'Data',
                'data' => [
                    'name' => $name,
                ],
            ]
        );
        $this->assertIsCreatedResponse($response, false);
    }

    public function testDeleteElementFileSuccess204(): void
    {
        $elementId = '6f7a8b9c-0d1e-4f2a-3b4c-5d6e7f8a9b0c';
        $this->createElement($elementId, 'delete-element-file-204');

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

        $documentationHeadersPath = 'docs/api-endpoints/file/delete-element-file/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteElementFileFailure401(): void
    {
        $elementId = '7a8b9c0d-1e2f-4a3b-4c5d-6e7f8a9b0c1d';
        $this->createElement($elementId, 'delete-element-file-401');

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementId), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'docs/api-endpoints/file/delete-element-file/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/delete-element-file/401-response-body.json';
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
        $elementWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementWhichDoesNotExist), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'docs/api-endpoints/file/delete-element-file/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/delete-element-file/404-response-body.json';
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
