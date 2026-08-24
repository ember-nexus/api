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
}
