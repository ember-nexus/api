<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteFileTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testDeleteFileFromNode(): void
    {
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

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('%s/file', $location),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);

        // verify file is accessible
        $getFileResponse = $this->runGetRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsBinaryStreamResponse($getFileResponse, 'image/jpeg');

        $deleteFileResponse = $this->runDeleteRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsDeletedResponse($deleteFileResponse);

        // verify file is no longer accessible
        $getFileResponse = $this->runGetRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsProblemResponse($getFileResponse, 404);
    }

    public function testFileCanBeCreatedAgainAfterDeletion(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'file-can-be-created-again-after-deletion'],
        ]));

        $this->assertIsCreatedResponse($this->runUploadRequest('POST', sprintf('/%s/file', $elementId), 'first content', self::TOKEN), false);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN));

        // the deleted file must not block a new POST with 409
        $this->assertIsCreatedResponse($this->runUploadRequest('POST', sprintf('/%s/file', $elementId), 'second content', self::TOKEN), false);
        $this->assertSame('second content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testDeleteFileFromRelation(): void
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
        $location = $relation->getHeader('Location')[0];

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $response = $this->runUploadRequest(
            'PUT',
            sprintf('%s/file', $location),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($response, false);

        // verify file is accessible
        $getFileResponse = $this->runGetRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsBinaryStreamResponse($getFileResponse, 'image/jpeg');

        // verify relation has file-property
        $getResponse = $this->runGetRequest(
            sprintf('%s', $location),
            self::TOKEN
        );
        $data = json_decode((string) $getResponse->getBody(), true);
        $this->assertSame(
            [
                'contentLength' => 63933,
                'extension' => 'bin',
                'mimeType' => 'image/jpeg',
                'hash' => [
                    'sha256' => hash_file('sha256', __DIR__.'/../../Asset/cherry-blossoms.jpg'),
                ],
            ],
            $data['file']
        );

        $deleteFileResponse = $this->runDeleteRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsDeletedResponse($deleteFileResponse);

        // verify file is no longer accessible
        $getFileResponse = $this->runGetRequest(
            sprintf('%s/file', $location),
            self::TOKEN
        );
        $this->assertIsProblemResponse($getFileResponse, 404);
    }
}
