<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\File;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

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
    public function testPostElementFileSuccess201(): void
    {
        $elementId = '02IiNr0G8viOVKkdSjRkVgMCgYOqgcE42rGtX';
        $this->createDocumentedElement($elementId, 'post-element-file-201', self::TOKEN);

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

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/201-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostElementFileSuccess204WithInitialData(): void
    {
        $elementId = '02IiNr0G8viOVKkTirl2u7j6UfAgy3cgl0Upz';
        $this->createDocumentedElement($elementId, 'post-element-file-204-with-data', self::TOKEN);

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

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/204-with-initial-data-response-header.txt';
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
        $elementId = '02IiNr0G8viOVKjgKIojikXMC6YRQrFk2Gwh7';
        $this->createDocumentedElement($elementId, 'post-element-file-204-without-data', self::TOKEN);

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

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/204-without-initial-data-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostElementFileFailure400(): void
    {
        $elementId = '02IiNr0G8viOVKkz8EJio85ZLlQT7gyFlHpkv';
        $this->createDocumentedElement($elementId, 'post-element-file-400', self::TOKEN);

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

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/400-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/post-element-file/400-response-body.json';
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
        $elementId = '02IiNr0G8viOVKlFq6Io3jLWcD5xKrPo2Iok7';
        $this->createDocumentedElement($elementId, 'post-element-file-401', self::TOKEN);

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            'thisTokenDoesNotExist',
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/post-element-file/401-response-body.json';
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
        $elementWhichDoesNotExist = '02IiNr0G8viOVKkkt30R04iI2cjxQKZ9bnCYA';

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementWhichDoesNotExist),
            '',
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/404-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/post-element-file/404-response-body.json';
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
        $elementId = '02IiNr0G8viOVKkBL8rALzaMl9m8afk0C4BBj';
        $this->createDocumentedElement($elementId, 'post-element-file-409', self::TOKEN);

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

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/post-element-file/409-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/post-element-file/409-response-body.json';
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
