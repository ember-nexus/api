<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies that continuing a resumable upload with an Upload-Offset which does not match the upload's actual
 * offset results in the correct conflict error.
 */
#[Group('test')]
class PatchUploadIncorrectOffsetTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 24242424;
    private const int FILE_SIZE = 6 * 1024 * 1024;
    private const string FILE_PATH = __DIR__.'/../../Asset/patch-upload-incorrect-offset.bin';

    public function testPatchUploadWithIncorrectOffsetReturnsConflict(): void
    {
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, self::FILE_PATH);
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::FILE_SIZE);

        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'patch-upload-incorrect-offset',
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
                'Content-Disposition' => 'inline; filename=patch-upload-incorrect-offset.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);
        $actualOffset = (int) $createUploadResponse->getHeader('Upload-Offset')[0];

        $incorrectOffset = $actualOffset + 1;
        $bogusChunkPath = __DIR__.'/../../Asset/patch-upload-incorrect-offset-chunk.bin';
        $this->generateDeterministicFile(self::FILE_SEED + 1, 1024, $bogusChunkPath);
        $bogusChunk = \Safe\fopen($bogusChunkPath, 'r');

        $patchResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $bogusChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $incorrectOffset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponse, 409);
        $body = $this->getBody($patchResponse);
        $this->assertSame($actualOffset, $body['expected-offset']);
        $this->assertSame($incorrectOffset, $body['provided-offset']);

        $this->cleanupChunks($chunks);
        unlink(self::FILE_PATH);
        unlink($bogusChunkPath);
    }
}
