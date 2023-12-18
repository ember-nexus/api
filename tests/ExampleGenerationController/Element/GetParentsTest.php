<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetParentsTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string CHILD_UUID = '45482998-274a-43d0-a466-f31d0b24cc0a';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testGetParentsSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::CHILD_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-parents/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-parents/200-response-body.json';
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

    public function testGetParentsFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::CHILD_UUID), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-parents/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-parents/401-response-body.json';
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

    public function testGetParentsFailure404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::ELEMENT_WHICH_DOES_NOT_EXIST), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-parents/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-parents/404-response-body.json';
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
