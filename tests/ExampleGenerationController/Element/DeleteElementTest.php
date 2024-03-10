<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class DeleteElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    private const string TOKEN = 'secret-token:CevJS3ZkDtJcCdqEhFKqWF';
    private const string ELEMENT = '55cce573-1377-4781-be16-8b81587aca10';
    private const string NON_EXISTENT_ELEMENT = '00000000-0000-4000-8000-000000000000';

    public function testDeleteElementFailure412(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::ELEMENT), self::TOKEN, ['If-Match' => '"etagDoesNotExist"']);
        $this->assertIsProblemResponse($response, 412);
        $documentationHeadersPath = 'docs/api-endpoints/element/delete-element/412-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/delete-element/412-response-body.json';
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

    public function testDeleteElementSuccess(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::ELEMENT), self::TOKEN);
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/element/delete-element/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testDeleteElementFailure401(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::ELEMENT), 'tokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/delete-element/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/delete-element/401-response-body.json';
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

    public function testDeleteElementFailure404(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::NON_EXISTENT_ELEMENT), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/delete-element/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/delete-element/404-response-body.json';
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
