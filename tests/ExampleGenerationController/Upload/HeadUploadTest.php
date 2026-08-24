<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Upload;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own element and, where needed, its own in-progress resumable upload, so none of them
 * depend on, or interfere with, each other. Upload ids are always server-generated, so unlike element ids they
 * can't be pinned to a fixed value - this is fine here, since none of these responses echo the upload id back
 * into their body, and the 'Location' header (the only place it would otherwise appear) is already excluded from
 * the header comparison.
 */
class HeadUploadTest extends BaseRequestTestCase
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

    public function testHeadUploadSuccess204(): void
    {
        $elementId = '8b9c0d1e-2f3a-4b4c-5d6e-7f8a9b0c1d2e';
        $this->createElement($elementId, 'head-upload-204');
        $uploadId = $this->createResumableUpload($elementId, 'example-head-upload-204.bin');

        $response = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $response->getStatusCode());

        $documentationHeadersPath = 'docs/api-endpoints/upload/head-upload/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    /**
     * A HEAD response never carries a body (RFC 9110, Section 9.3.2), so unlike the other endpoints' failure
     * examples here, only the headers are documented/verified - there's no body to show.
     */
    public function testHeadUploadFailure401(): void
    {
        $elementId = '9c0d1e2f-3a4b-4c5d-6e7f-8a9b0c1d2e3f';
        $this->createElement($elementId, 'head-upload-401');
        $uploadId = $this->createResumableUpload($elementId, 'example-head-upload-401.bin');

        $response = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), 'thisTokenDoesNotExist');
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('application/problem+json; charset=utf-8', $response->getHeader('content-type')[0]);

        $documentationHeadersPath = 'docs/api-endpoints/upload/head-upload/401-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testHeadUploadFailure404(): void
    {
        $uploadWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runHeadRequest(sprintf('/upload/%s', $uploadWhichDoesNotExist), self::TOKEN);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('application/problem+json; charset=utf-8', $response->getHeader('content-type')[0]);

        $documentationHeadersPath = 'docs/api-endpoints/upload/head-upload/404-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }
}
