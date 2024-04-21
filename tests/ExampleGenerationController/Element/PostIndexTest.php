<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationController\Element;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostIndexTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = 'b4117ae0-1241-479f-b363-45f290ec7fc7';

    public function testPostIndexSuccess(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-index/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPostIndexError400(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-index/400-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/post-index/400-response-body.json';
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

    public function testPostIndexError401(): void
    {
        $response = $this->runPostRequest(
            '/',
            'badToken',
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-index/401-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/post-index/401-response-body.json';
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

    public function testPostIndexError404(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'start' => '7b80b203-2b82-40f5-accd-c7089fe6114e',
                'end' => self::ELEMENT_WHICH_DOES_NOT_EXIST,
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'docs/api-endpoints/element/post-index/404-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/element/post-index/404-response-body.json';
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
