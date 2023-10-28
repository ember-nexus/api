<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetIndexTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';
    private const TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';

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
}
