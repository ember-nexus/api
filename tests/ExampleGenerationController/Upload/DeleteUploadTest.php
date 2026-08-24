<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Upload;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own element and its own in-progress resumable upload, so none of them depend on, or
 * interfere with, each other. See HeadUploadTest for why upload ids (unlike element ids) are not pinned to a
 * fixed value.
 */
class DeleteUploadTest extends BaseRequestTestCase
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

    private function createResumableUpload(string $elementId, string $assetName): string
    {
        $filePath = __DIR__.'/../Asset/'.$assetName;
        // non-final chunks must meet the server's configured minimum chunk size
        $this->generateDeterministicFile(crc32($assetName), 5 * 1024 * 1024, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);
        unlink($filePath);

        return $this->getUuidFromLocation($response);
    }

    public function testDeleteUploadSuccess204(): void
    {
        $elementId = 'a0b1c2d3-4e5f-4a6b-7c8d-9e0f1a2b3c4d';
        $this->createElement($elementId, 'delete-upload-204');
        $uploadId = $this->createResumableUpload($elementId, 'example-delete-upload-204.bin');

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertNoContentResponse($response);

        $documentationHeadersPath = 'docs/api-endpoints/upload/delete-upload/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteUploadFailure401(): void
    {
        $elementId = 'b1c2d3e4-5f6a-4b7c-8d9e-0f1a2b3c4d5e';
        $this->createElement($elementId, 'delete-upload-401');
        $uploadId = $this->createResumableUpload($elementId, 'example-delete-upload-401.bin');

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'docs/api-endpoints/upload/delete-upload/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/delete-upload/401-response-body.json';
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

    public function testDeleteUploadFailure404(): void
    {
        $uploadWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadWhichDoesNotExist), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'docs/api-endpoints/upload/delete-upload/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/delete-upload/404-response-body.json';
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
