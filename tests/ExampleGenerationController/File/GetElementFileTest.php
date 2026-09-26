<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\File;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

/**
 * Uses the read-only "Rose" image from the "general.botanicExample" reference dataset scenario. Since every test
 * here only reads the file, none of them mutate shared state, so the fixed fixture can be reused across all of
 * them without the tests influencing each other.
 */
class GetElementFileTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ROSE_ID = '0fdd52ba-55da-430c-b015-3277a231e895';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testGetElementFileSuccess200(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/200-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testGetElementFileSuccess206(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );
        $this->assertSame(206, $response->getStatusCode());
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/206-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testGetElementFileFailure400(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=not-a-valid-range']
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/get-element-file/400-response-body.json';
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

    public function testGetElementFileFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/get-element-file/401-response-body.json';
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

    public function testGetElementFileFailure404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ELEMENT_WHICH_DOES_NOT_EXIST), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/get-element-file/404-response-body.json';
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

    public function testGetElementFileFailure416(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=999999999-']
        );
        $this->assertIsProblemResponse($response, 416);
        $documentationHeadersPath = 'docs/api-endpoints/file/get-element-file/416-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/file/get-element-file/416-response-body.json';
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

    /**
     * Creates a small element with a short text file, to showcase `If-Range` with readable data.
     *
     * @return string the current file ETag
     */
    private function createElementWithShortTextFile(string $elementId, string $name): string
    {
        $response = $this->runPostRequest('/', self::TOKEN, [
            'id' => $elementId,
            'type' => 'Data',
            'data' => ['name' => $name],
        ]);
        $this->assertIsCreatedResponse($response, false);

        $response = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            'Hello, Ember Nexus!',
            self::TOKEN,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=hello.txt',
            ]
        );
        $this->assertIsCreatedResponse($response, false);

        $response = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $response->getStatusCode());

        return $response->getHeader('ETag')[0];
    }

    public function testGetElementFileSuccess206WithMatchingIfRange(): void
    {
        $elementId = '3c4d5e6f-7a8b-4c9d-8e0f-2a3b4c5d6e7f';
        $etag = $this->createElementWithShortTextFile($elementId, 'get-element-file-if-range-match');

        // the ETag of the file still matches: the client resumes its download, the requested range is returned
        $response = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN,
            ['Range' => 'bytes=7-11', 'If-Range' => $etag]
        );
        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame('Ember', (string) $response->getBody());
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            'docs/api-endpoints/file/get-element-file/206-if-range-match-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'docs/api-endpoints/file/get-element-file/206-if-range-match-response-body.txt',
            $response,
            false
        );
    }

    public function testGetElementFileSuccess200WithNonMatchingIfRange(): void
    {
        $elementId = '4d5e6f7a-8b9c-4d0e-9f1a-3b4c5d6e7f8a';
        $this->createElementWithShortTextFile($elementId, 'get-element-file-if-range-mismatch');

        // the file changed since the client started its download: the Range header is ignored and the full file is
        // returned, so the client never mixes parts of two different versions
        $response = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN,
            ['Range' => 'bytes=7-11', 'If-Range' => '"outdated-etag"']
        );
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello, Ember Nexus!', (string) $response->getBody());
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            'docs/api-endpoints/file/get-element-file/200-if-range-mismatch-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'docs/api-endpoints/file/get-element-file/200-if-range-mismatch-response-body.txt',
            $response,
            false
        );
    }
}
