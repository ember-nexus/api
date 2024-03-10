<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetIndexTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';

    public function testGetIndexSuccess(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 3);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-index/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-index/200-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'created',
                'updated',
            ]
        );
    }

    public function testGetIndexRedirect304(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $etag = $response->getHeader('ETag');
        $response = $this->runGetRequest('/', self::TOKEN, ['If-None-Match' => $etag]);
        $this->assertNotModifiedResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-index/304-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testGetIndexFailure401(): void
    {
        $response = $this->runGetRequest('/', 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-index/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-index/401-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'created',
                'updated',
            ]
        );
    }

    public function testGetElementFailure412(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN, ['If-Match' => '"etagDoesNotExist"']);
        $this->assertIsProblemResponse($response, 412);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-index/412-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-index/412-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            true,
            [
                'created',
                'updated',
            ]
        );
    }
}
