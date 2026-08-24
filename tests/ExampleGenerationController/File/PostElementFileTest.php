<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\File;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own "Data" element before uploading a file to it, so none of them depend on, or
 * interfere with, shared state or each other.
 */
class PostElementFileTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * The element is created with a fixed id (rather than letting the server assign a random one), so that
     * error responses which echo the id back (e.g. the 409 below) stay identical across runs.
     */
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

    public function testPostElementFileSuccess201(): void
    {
        $elementId = 'a4dfda49-96db-4b58-95f4-4a6cae835674';
        $this->createElement($elementId, 'post-element-file-201');

        $filePath = __DIR__.'/../Asset/example-post-element-file-201.bin';
        $this->generateDeterministicFile(11111111, 2048, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename=example.bin',
            ]
        );
        $this->assertIsCreatedResponse($response, false);
        unlink($filePath);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/201-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostElementFileSuccess204WithInitialData(): void
    {
        $elementId = '90db2934-3f27-4a37-8e05-a03ea3e5e5f2';
        $this->createElement($elementId, 'post-element-file-204-with-data');

        // non-final chunks must meet the server's configured minimum chunk size (advertised via 'Upload-Limit')
        $filePath = __DIR__.'/../Asset/example-post-element-file-204-with-data.bin';
        $this->generateDeterministicFile(22222222, 5 * 1024 * 1024, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename=example.bin',
            ]
        );
        $this->assertNoContentResponse($response, true);
        unlink($filePath);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/204-with-initial-data-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    /**
     * A resumable upload may be created with no initial data at all, i.e. an empty body alongside
     * `Upload-Complete: ?0` - explicitly allowed by the resumable upload draft standard unless disabled, which
     * this API does not do.
     */
    public function testPostElementFileSuccess204WithoutInitialData(): void
    {
        $elementId = '2b6a4c9f-2c9d-4b2a-9f1b-3b8ea3c7f6a1';
        $this->createElement($elementId, 'post-element-file-204-without-data');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/204-without-initial-data-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostElementFileFailure400(): void
    {
        $elementId = 'd15c34e0-4c8a-4d1b-9f5c-7e3a1b2c4d5e';
        $this->createElement($elementId, 'post-element-file-400');

        $filePath = __DIR__.'/../Asset/example-post-element-file-400.bin';
        $this->generateDeterministicFile(33333333, 2048, $filePath);

        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        unlink($filePath);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/post-element-file/400-response-body.json';
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

    public function testPostElementFileFailure401(): void
    {
        $elementId = 'f3a1b2c4-5d6e-4f7a-8b9c-0d1e2f3a4b5c';
        $this->createElement($elementId, 'post-element-file-401');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            'thisTokenDoesNotExist',
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/post-element-file/401-response-body.json';
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

    public function testPostElementFileFailure404(): void
    {
        $elementWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementWhichDoesNotExist),
            '',
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/post-element-file/404-response-body.json';
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

    public function testPostElementFileFailure409(): void
    {
        $elementId = '6b1c2d3e-4f5a-4b6c-8d9e-0f1a2b3c4d5e';
        $this->createElement($elementId, 'post-element-file-409');

        $filePath = __DIR__.'/../Asset/example-post-element-file-409.bin';
        $this->generateDeterministicFile(44444444, 2048, $filePath);

        $firstFile = \Safe\fopen($filePath, 'r');
        $firstResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstFile,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($firstResponse, false);

        $secondFile = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $secondFile,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 409);
        unlink($filePath);

        $documentationHeadersPath = 'docs/api-endpoints/file/post-element-file/409-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/post-element-file/409-response-body.json';
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
