<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that a file is removed from storage once the element it was attached to is deleted, even though the
 * file itself was never explicitly deleted through the file endpoint.
 */
class FileRemovedWhenElementRemovedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testFileIsGoneAfterElementIsDeleted(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-removed-when-element-removed',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $getFileResponse1 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($getFileResponse1, 'image/jpeg');

        $this->assertFileExistsInStorage($elementId);

        $deleteElementResponse = $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteElementResponse);

        $getElementResponse = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getElementResponse, 404);

        $getFileResponse2 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getFileResponse2, 404);

        $this->assertFileDoesNotExistInStorage($elementId);
    }

    public function testFileOfAttachedRelationIsRemovedWhenNodeIsDeleted(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'file-removed-when-node-removed');
        $relation = $this->getBody($this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN));

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest('POST', sprintf('/%s/file', $relationId), $file, self::TOKEN);
        $this->assertIsCreatedResponse($postFileResponse, false);
        $this->assertFileExistsInStorage($relationId);

        // deleting the start node also deletes the attached relation, and therefore its file
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $relation['start']), self::TOKEN));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN), 404);
        $this->assertFileDoesNotExistInStorage($relationId);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $relation['end']), self::TOKEN));
    }
}
