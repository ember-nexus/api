<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\File;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Every test creates its own "Data" element (with a fixed id, so responses which echo it back stay identical
 * across runs) before exercising the endpoint, so none of them depend on, or interfere with, each other.
 */
class PutElementFileTest extends BaseRequestTestCase
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

    public function testPutElementFileSuccess201(): void
    {
        $elementId = '1a2b3c4d-5e6f-4a7b-8c9d-0e1f2a3b4c5d';
        $this->createElement($elementId, 'put-element-file-201');

        $filePath = __DIR__.'/../Asset/example-put-element-file-201.bin';
        $this->generateDeterministicFile(55555555, 2048, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'PUT',
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

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/201-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPutElementFileSuccess204WithInitialData(): void
    {
        $elementId = '2b3c4d5e-6f7a-4b8c-9d0e-1f2a3b4c5d6e';
        $this->createElement($elementId, 'put-element-file-204-with-data');

        // non-final chunks must meet the server's configured minimum chunk size (advertised via 'Upload-Limit')
        $filePath = __DIR__.'/../Asset/example-put-element-file-204-with-data.bin';
        $this->generateDeterministicFile(66666666, 5 * 1024 * 1024, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'PUT',
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

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/204-with-initial-data-response-header.txt';
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
    public function testPutElementFileSuccess204WithoutInitialData(): void
    {
        $elementId = '3c4d5e6f-7a8b-4c9d-0e1f-2a3b4c5d6e7f';
        $this->createElement($elementId, 'put-element-file-204-without-data');

        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/204-without-initial-data-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    /**
     * PUT also allows replacing a file the element already has - here, the replacement is rejected because its
     * declared digest doesn't match, which must leave the original file usable (not just return an error).
     */
    public function testPutElementFileFailure400(): void
    {
        $elementId = '4d5e6f7a-8b9c-4d0e-1f2a-3b4c5d6e7f8a';
        $this->createElement($elementId, 'put-element-file-400');

        $originalFilePath = __DIR__.'/../Asset/example-put-element-file-400-original.bin';
        $this->generateDeterministicFile(77777777, 2048, $originalFilePath);
        $originalFile = \Safe\fopen($originalFilePath, 'r');
        $originalUploadResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $originalFile,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($originalUploadResponse, false);
        unlink($originalFilePath);

        $replacementFilePath = __DIR__.'/../Asset/example-put-element-file-400-replacement.bin';
        $this->generateDeterministicFile(88888888, 2048, $replacementFilePath);
        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $replacementFile = \Safe\fopen($replacementFilePath, 'r');
        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $replacementFile,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        unlink($replacementFilePath);

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/put-element-file/400-response-body.json';
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

    public function testPutElementFileFailure401(): void
    {
        $elementId = '5e6f7a8b-9c0d-4e1f-2a3b-4c5d6e7f8a9b';
        $this->createElement($elementId, 'put-element-file-401');

        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            '',
            'thisTokenDoesNotExist',
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 401);

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/put-element-file/401-response-body.json';
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

    public function testPutElementFileFailure404(): void
    {
        $elementWhichDoesNotExist = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementWhichDoesNotExist),
            '',
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsProblemResponse($response, 404);

        $documentationHeadersPath = 'docs/api-endpoints/file/put-element-file/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/put-element-file/404-response-body.json';
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
