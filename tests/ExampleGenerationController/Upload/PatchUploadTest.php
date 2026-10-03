<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Upload;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own element and its own in-progress resumable upload, so none of them depend on, or
 * interfere with, each other. See HeadUploadTest for why upload ids (unlike element ids) are not pinned to a
 * fixed value.
 */
class PatchUploadTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

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

    /**
     * @return array{0: string, 1: string[]} the created upload's id, and the paths of the file chunks it was
     *                                       split into (two chunks: one already uploaded, one not yet)
     */
    private function createResumableUploadWithOneChunkRemaining(string $elementId, string $assetName): array
    {
        $filePath = __DIR__.'/../Asset/'.$assetName;
        $this->generateDeterministicFile(crc32($assetName), 2 * self::CHUNK_SIZE, $filePath);
        $chunks = $this->splitFileToChunks($filePath, self::CHUNK_SIZE);
        unlink($filePath);

        $firstChunk = \Safe\fopen($chunks[0], 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);

        return [$this->getUuidFromLocation($response), $chunks];
    }

    public function testPatchUploadSuccess204IntermediateChunk(): void
    {
        $elementId = 'c2d3e4f5-6a7b-4c8d-9e0f-1a2b3c4d5e6f';
        $this->createElement($elementId, 'patch-upload-204-intermediate');

        // three chunks, so the second PATCH (tested here) is still an intermediate one, not the final chunk
        $filePath = __DIR__.'/../Asset/example-patch-upload-204-intermediate.bin';
        $this->generateDeterministicFile(crc32('patch-upload-204-intermediate'), 3 * self::CHUNK_SIZE, $filePath);
        $chunks = $this->splitFileToChunks($filePath, self::CHUNK_SIZE);
        unlink($filePath);

        $firstChunk = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($response);
        $this->cleanupChunks($chunks);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/204-intermediate-chunk-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPatchUploadSuccess204FinalChunk(): void
    {
        $elementId = 'd3e4f5a6-7b8c-4d9e-0f1a-2b3c4d5e6f7a';
        $this->createElement($elementId, 'patch-upload-204-final');
        [$uploadId, $chunks] = $this->createResumableUploadWithOneChunkRemaining($elementId, 'example-patch-upload-204-final.bin');

        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($response);
        $this->cleanupChunks($chunks);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/204-final-chunk-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPatchUploadFailure400(): void
    {
        $elementId = 'e4f5a6b7-8c9d-4e0f-1a2b-3c4d5e6f7a8b';
        $this->createElement($elementId, 'patch-upload-400');
        [$uploadId, $chunks] = $this->createResumableUploadWithOneChunkRemaining($elementId, 'example-patch-upload-400.bin');

        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $this->cleanupChunks($chunks);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/patch-upload/400-response-body.json';
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

    public function testPatchUploadFailure409(): void
    {
        $elementId = 'f5a6b7c8-9d0e-4f1a-2b3c-4d5e6f7a8b9c';
        $this->createElement($elementId, 'patch-upload-409');
        [$uploadId, $chunks] = $this->createResumableUploadWithOneChunkRemaining($elementId, 'example-patch-upload-409.bin');

        $incorrectOffset = self::CHUNK_SIZE + 1;
        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $incorrectOffset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($response, 409);
        $this->cleanupChunks($chunks);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/409-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/patch-upload/409-response-body.json';
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

    public function testPatchUploadFailure401(): void
    {
        $elementId = 'a6b7c8d9-0e1f-4a2b-3c4d-5e6f7a8b9c0d';
        $this->createElement($elementId, 'patch-upload-401');
        [$uploadId, $chunks] = $this->createResumableUploadWithOneChunkRemaining($elementId, 'example-patch-upload-401.bin');

        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            'thisTokenDoesNotExist',
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $this->cleanupChunks($chunks);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/patch-upload/401-response-body.json';
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

    public function testPatchUploadFailure404(): void
    {
        $uploadWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadWhichDoesNotExist),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'docs/api-endpoints/upload/patch-upload/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/upload/patch-upload/404-response-body.json';
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
