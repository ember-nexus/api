<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\File;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Uses the read-only "Rose" image from the "general.botanicExample" reference dataset scenario. Since every test
 * here only reads the file, none of them mutate shared state, so the fixed fixture can be reused across all of
 * them without the tests influencing each other.
 */
class GetElementFileTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ROSE_ID = '02IiNr0GOoKiPMLEezecUZIcqABdykANm0j0F';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = '02IiNr0G8viOVKkkt30R04iI2cjxQKZ9bnCYA';

    public function testGetElementFileSuccess200(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/200-response-header.txt';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/206-response-header.txt';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/400-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/get-element-file/400-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/get-element-file/401-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/404-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/get-element-file/404-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/file/get-element-file/416-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/file/get-element-file/416-response-body.json';
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
        $elementId = '02IiNr0G8viOVKjoXo9GQtbb1IqbssnjA1lz7';
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
            'tests/Recordings/api-endpoints/file/get-element-file/206-if-range-match-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'tests/Recordings/api-endpoints/file/get-element-file/206-if-range-match-response-body.txt',
            $response,
            false
        );
    }

    public function testGetElementFileSuccess200WithNonMatchingIfRange(): void
    {
        $elementId = '02IiNr0G8viOVKjwqxXbwlS0qONXk2nfSH8Gw';
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
            'tests/Recordings/api-endpoints/file/get-element-file/200-if-range-mismatch-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'tests/Recordings/api-endpoints/file/get-element-file/200-if-range-mismatch-response-body.txt',
            $response,
            false
        );
    }
}
