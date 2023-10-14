<?php

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostElementTest extends BaseRequestTestCase
{
    private const PATH_TO_ROOT = __DIR__.'/../../../';
    private const TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const PARENT_ELEMENT = '7b80b203-2b82-40f5-accd-c7089fe6114e';

    public function testPostElementSuccess(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::PARENT_ELEMENT),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-element/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostElementError400(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::PARENT_ELEMENT),
            self::TOKEN,
            [
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-element/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/post-element/400-response-body.json';
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

    public function testPostElementError401(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::PARENT_ELEMENT),
            'badToken',
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-element/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/post-element/401-response-body.json';
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
