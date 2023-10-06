<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetRelatedTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';
    private const TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const ELEMENT_UUID = '45482998-274a-43d0-a466-f31d0b24cc0a';
    private const ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testGetRelatedSuccess(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::ELEMENT_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 3);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-related/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-related/200-response-body.json';
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

    public function testGetRelatedFailure401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::ELEMENT_WHICH_DOES_NOT_EXIST), 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-related/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-related/401-response-body.json';
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

    public function testGetRelatedFailure404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::ELEMENT_WHICH_DOES_NOT_EXIST), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/get-related/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/get-related/404-response-body.json';
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
