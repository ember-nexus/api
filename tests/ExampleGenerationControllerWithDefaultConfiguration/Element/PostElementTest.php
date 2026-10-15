<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Element;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class PostElementTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string PARENT_ELEMENT = '7b80b203-2b82-40f5-accd-c7089fe6114e';
    // a relation the test user can read but has no CREATE access to (neither its start nor end node is owned by
    // them), used to assert the "no access" 404 case
    private const string RELATION_WITHOUT_CREATE_ACCESS = 'eb5d2879-c5e6-43a6-b8ce-2a1188ca7073';

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
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/post-element/201-response-header.txt';
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
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/post-element/400-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/post-element/400-response-body.json';
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
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/post-element/401-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/post-element/401-response-body.json';
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

    /**
     * Regression test: the parent being a relation the user has CREATE access to must answer 400 (see
     * testPostElementError400ParentIsRelation()), not 404. 404 is reserved for a parent the user has no access to at
     * all (including because it does not exist); this covers that case using a relation the user can read but not
     * create under.
     */
    public function testPostElementError404(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::RELATION_WITHOUT_CREATE_ACCESS),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 404);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/post-element/404-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/post-element/404-response-body.json';
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

    /**
     * Regression test for a parent that is a relation the user DOES have CREATE access to (both its start and end
     * node are owned by the test user, via {@see createEphemeralRelation()}): this must answer 400 ("relations can
     * not own nodes" is a request-shape problem), not 404 (which is reserved for a parent the user has no access to
     * at all).
     */
    public function testPostElementError400ParentIsRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'post-element-parent-is-relation');

        $response = $this->runPostRequest(
            sprintf('/%s', $relationId),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'hello' => 'world',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        $documentationHeadersPath = 'tests/recordings/api-endpoints/element/post-element/400-parent-is-relation-response-header.txt';
        $documentationBodyPath = 'tests/recordings/api-endpoints/element/post-element/400-parent-is-relation-response-body.json';
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

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
