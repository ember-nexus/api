<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class DeleteFileTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testDeleteFileFromNode(): void
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

        // upload file
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
        $this->assertIsBinaryStreamResponse($getFileResponse, 'application/octet-stream');

        // delete file
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

        // upload file
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
        $this->assertIsBinaryStreamResponse($getFileResponse, 'application/octet-stream');

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
                'hashAlgorithm' => 'sha256',
                'hash' => hash_file('sha256', __DIR__.'/../../Asset/cherry-blossoms.jpg'),
            ],
            $data['file']
        );

        // delete file
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
