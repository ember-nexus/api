<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Upload;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Every test creates its own element and its own in-progress resumable upload, so none of them depend on, or
 * interfere with, each other. See HeadUploadTest for why upload ids (unlike element ids) are not pinned to a
 * fixed value.
 */
class DeleteUploadTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

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
        $elementId = '02IiNr0G8viOVKkbS5VsCenZ94GE6cJ0BP32T';
        $this->createDocumentedElement($elementId, 'delete-upload-204', self::TOKEN);
        $uploadId = $this->createResumableUpload($elementId, 'example-delete-upload-204.bin');

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertNoContentResponse($response);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/upload/delete-upload/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteUploadFailure401(): void
    {
        $elementId = '02IiNr0G8viOVKkjlEuDsUsAsTs15F1LRQdfZ';
        $this->createDocumentedElement($elementId, 'delete-upload-401', self::TOKEN);
        $uploadId = $this->createResumableUpload($elementId, 'example-delete-upload-401.bin');

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/upload/delete-upload/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/upload/delete-upload/401-response-body.json';
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
        $uploadWhichDoesNotExist = '02IiNr0G8viOVKkkt30R04iI2cjxQKZ9bnCYA';

        $response = $this->runDeleteRequest(sprintf('/upload/%s', $uploadWhichDoesNotExist), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/upload/delete-upload/404-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/upload/delete-upload/404-response-body.json';
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
