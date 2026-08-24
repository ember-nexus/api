<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the simplest possible file lifecycle: create an element, attach a file directly with POST, replace it
 * with PUT, delete it, then verify it is gone.
 */
#[Group('test')]
class SimpleFileInteractivityTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testCreateElementPostFilePutReplaceDeleteThenGetIsNotFound(): void
    {
        // create element
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'simple-file-interactivity',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        // post file directly
        $firstFile = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstFile,
            self::TOKEN,
            [
                'Content-Disposition' => 'inline; filename=cherry-blossoms.jpg',
                'Content-Type' => 'image/jpg',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $getFileResponse1 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($getFileResponse1, 'application/octet-stream');
        $this->assertSame(63933, strlen((string) $getFileResponse1->getBody()));

        // put replace file
        $secondFilePath = __DIR__.'/../../Asset/simple-file-interactivity-replacement.bin';
        $this->generateDeterministicFile(24681012, 16 * 1024, $secondFilePath);
        $secondFile = \Safe\fopen($secondFilePath, 'r');
        $putFileResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $secondFile,
            self::TOKEN,
            [
                'Content-Disposition' => 'inline; filename=simple-file-interactivity-replacement.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($putFileResponse, false);

        $getFileResponse2 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($getFileResponse2, 'application/octet-stream');
        $this->assertNotSame(
            (string) $getFileResponse1->getBody(),
            (string) $getFileResponse2->getBody()
        );

        unlink($secondFilePath);

        // delete file
        $deleteFileResponse = $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteFileResponse);

        // get file -> 404
        $getFileResponse3 = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getFileResponse3, 404);
    }
}
