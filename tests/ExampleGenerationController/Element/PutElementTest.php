<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PutElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string ELEMENT = '251654a1-588e-4dc4-836f-b1e334d91aae';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = '00000000-0000-4000-8000-000000000000';

    public function testPutElementSuccess(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::ELEMENT),
            self::TOKEN,
            [
                'hello' => 'world',
            ]
        );
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/element/put-element/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPutElementError401(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::ELEMENT),
            'badToken',
            [
                'hello' => 'world',
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/put-element/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/put-element/401-response-body.json';
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

    public function testPutElementError404(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::ELEMENT_WHICH_DOES_NOT_EXIST),
            self::TOKEN,
            [
                'hello' => 'world',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/put-element/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/put-element/404-response-body.json';
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

    public function testPutElementError412(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::ELEMENT),
            self::TOKEN,
            [
                'hello' => 'world',
            ],
            [
                'If-Match' => '"etagDoesNotExist"',
            ]
        );
        $this->assertIsProblemResponse($response, 412);
        $documentationHeadersPath = 'docs/api-endpoints/element/put-element/412-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/put-element/412-response-body.json';
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
