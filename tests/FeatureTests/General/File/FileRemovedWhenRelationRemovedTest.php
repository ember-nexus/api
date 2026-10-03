<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors FileRemovedWhenElementRemovedTest, but targets a relation instead of a node.
 */
class FileRemovedWhenRelationRemovedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testFileIsGoneAfterRelationIsDeleted(): void
    {
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-removed-when-relation-removed-start',
                ],
            ]
        );
        $startNodeId = $this->getUuidFromLocation($startNode);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-removed-when-relation-removed-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relationResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'file-removed-when-relation-removed',
                ],
            ]
        );
        $relationId = $this->getUuidFromLocation($relationResponse);

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $getFileResponse1 = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($getFileResponse1, 'image/jpeg');

        $this->assertFileExistsInStorage($relationId);

        $deleteRelationResponse = $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteRelationResponse);

        $getRelationResponse = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->assertIsProblemResponse($getRelationResponse, 404);

        $getFileResponse2 = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsProblemResponse($getFileResponse2, 404);

        $this->assertFileDoesNotExistInStorage($relationId);
    }
}
