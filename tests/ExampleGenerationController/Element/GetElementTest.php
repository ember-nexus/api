<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string NODE_UUID = '74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac';
    private const string RELATION_UUID = 'eb5d2879-c5e6-43a6-b8ce-2a1188ca7073';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testGetNodeSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Comment');
        $documentationHeadersPath = 'docs/api-endpoints/element/get-element/200-node-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-element/200-node-response-body.json';
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

    public function testGetRelationSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION_UUID), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $documentationHeadersPath = 'docs/api-endpoints/element/get-element/200-relation-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-element/200-relation-response-body.json';
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

    public function testGetElementFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-element/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-element/401-response-body.json';
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

    public function testGetElementFailure404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::ELEMENT_WHICH_DOES_NOT_EXIST), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-element/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-element/404-response-body.json';
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
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN, ['If-Match' => '"etagDoesNotExist"']);
        $this->assertIsProblemResponse($response, 412);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-element/412-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-element/412-response-body.json';
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
