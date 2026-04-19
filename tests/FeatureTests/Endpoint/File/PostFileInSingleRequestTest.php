<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class PostFileInSingleRequestTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testPostFileToNode(): void
    {
        // create new node for file upload
        $node = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $location = $node->getHeader('Location')[0];

        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('%s/file', $location),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);
    }

    public function testPostFileToNodeWithExistingFileFails(): void
    {
        // create new node for file upload
        $node = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $location = $node->getHeader('Location')[0];

        // request 1 - ok
        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('%s/file', $location),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);

        // request 2 - fail
        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('%s/file', $location),
            $file,
            self::TOKEN
        );
        $this->assertIsProblemResponse($response, 409);
    }

    public function testPostFileToRelation(): void
    {
        // create new relation for file upload
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $startNodeId = substr($startNode->getHeader('Location')[0], 1);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $endNodeId = substr($endNode->getHeader('Location')[0], 1);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $relationId = substr($relation->getHeader('Location')[0], 1);

        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);
    }

    public function testPostFileToRelationWithExistingFileFails(): void
    {
        // create new relation for file upload
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $startNodeId = substr($startNode->getHeader('Location')[0], 1);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $endNodeId = substr($endNode->getHeader('Location')[0], 1);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $relationId = substr($relation->getHeader('Location')[0], 1);

        // request 1 - ok
        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);

        // request 2 - fail
        $file = \Safe\fopen(__DIR__.'/Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN
        );
        $this->assertIsProblemResponse($response, 409);
    }
}
