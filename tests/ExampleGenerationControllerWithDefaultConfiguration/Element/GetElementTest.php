<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Element;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class GetElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string NODE_UUID = '02IiNr0GOoKiPMM1keIQn6X4HCRG6E9xAcDJ9';
    private const string RELATION_UUID = '02IiNr0GOoKiPMMxY2dBt9kLzyjTv2FqOIHUL';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = '02IiNr0G8viOVKkkt30R04iI2cjxQKZ9bnCYA';

    public function testGetNodeSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Comment');
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/200-node-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/element/get-element/200-node-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/200-relation-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/element/get-element/200-relation-response-body.json';
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

    public function testGetElementRedirect304(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $etag = $response->getHeader('ETag');
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN, ['If-None-Match' => $etag]);
        $this->assertNotModifiedResponse($response);
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/304-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testGetElementFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/401-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/element/get-element/401-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/404-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/element/get-element/404-response-body.json';
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
        $documentationHeadersPath = 'tests/Recordings/api-endpoints/element/get-element/412-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/element/get-element/412-response-body.json';
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
