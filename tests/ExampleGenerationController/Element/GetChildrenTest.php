<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetChildrenTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';
    private const TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const PARENT_UUID = '56fda20c-b238-4034-b555-1df47c47e17a';
    private const ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testGetChildrenSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::PARENT_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 6);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-children/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-children/200-response-body.json';
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

    public function testGetChildrenFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::PARENT_UUID), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-children/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-children/401-response-body.json';
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

    public function testGetChildrenFailure404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::ELEMENT_WHICH_DOES_NOT_EXIST), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-children/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-children/404-response-body.json';
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
