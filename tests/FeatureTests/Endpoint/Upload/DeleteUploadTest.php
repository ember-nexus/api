<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that deleting an in-progress resumable upload removes it, so that further HEAD or PATCH requests
 * against it fail.
 */
#[Group('test')]
class DeleteUploadTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 98765432;
    private const int FILE_SIZE = 6 * 1024 * 1024;
    private const string FILE_PATH = __DIR__.'/../../Asset/delete-upload.bin';

    public function testDeleteUploadThenHeadAndPatchFail(): void
    {
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, self::FILE_PATH);
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::FILE_SIZE);

        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'delete-upload',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $chunk1 = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $chunk1,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Disposition' => 'inline; filename=delete-upload.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);
        $actualOffset = (int) $createUploadResponse->getHeader('Upload-Offset')[0];

        // sanity check: upload exists before deletion
        $headResponseBeforeDelete = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponseBeforeDelete->getStatusCode());

        $deleteUploadResponse = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteUploadResponse);

        $headResponseAfterDelete = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(404, $headResponseAfterDelete->getStatusCode());

        $bogusChunkPath = __DIR__.'/../../Asset/delete-upload-chunk.bin';
        $this->generateDeterministicFile(self::FILE_SEED + 1, 1024, $bogusChunkPath);
        $bogusChunk = \Safe\fopen($bogusChunkPath, 'r');
        $patchResponseAfterDelete = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $bogusChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $actualOffset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponseAfterDelete, 404);

        $this->cleanupChunks($chunks);
        unlink(self::FILE_PATH);
        unlink($bogusChunkPath);
    }
}
