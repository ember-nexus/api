<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Element;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class PatchElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string ELEMENT = '84e67836-6455-489c-85d2-b57c70885b3b';
    private const string ELEMENT_WHICH_DOES_NOT_EXIST = '00000000-0000-4000-8000-000000000000';

    public function testPatchElementSuccess(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::ELEMENT),
            self::TOKEN,
            [
                'hello' => 'world',
            ]
        );
        $this->assertNoContentResponse($response);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/patch-element/204-response-header.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
    }

    public function testPatchElementError401(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::ELEMENT),
            'badToken',
            [
                'hello' => 'world',
            ]
        );
        $this->assertIsProblemResponse($response, 401);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/patch-element/401-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/patch-element/401-response-body.json';
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

    public function testPatchElementError404(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::ELEMENT_WHICH_DOES_NOT_EXIST),
            self::TOKEN,
            [
                'hello' => 'world',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/patch-element/404-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/patch-element/404-response-body.json';
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

    public function testPatchElementError400ForbiddenProperty(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::ELEMENT),
            self::TOKEN,
            [
                'created' => '2020-01-01T00:00:00+00:00',
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/patch-element/400-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/patch-element/400-response-body.json';
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

    public function testPatchElementError412(): void
    {
        $response = $this->runPatchRequest(
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
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/patch-element/412-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/patch-element/412-response-body.json';
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
