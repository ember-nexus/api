<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers the full resumable upload lifecycle: create an element, create an upload, upload chunks while
 * checking the upload status through HEAD in between, and finish the upload.
 */
#[Group('test')]
class ResumableUploadLifecycleTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 13571113;
    private const int CHUNK_SIZE = 5 * 1024 * 1024;
    private const int FILE_SIZE = 2 * self::CHUNK_SIZE;
    private const string FILE_PATH = __DIR__.'/../../Asset/resumable-upload-lifecycle.bin';

    public function testResumableUploadLifecycle(): void
    {
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, self::FILE_PATH);
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::CHUNK_SIZE);

        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'resumable-upload-lifecycle',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        // create the upload with the first chunk
        $chunk1 = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $chunk1,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Disposition' => 'inline; filename=resumable-upload-lifecycle.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $this->assertSame('?0', $createUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::CHUNK_SIZE, $createUploadResponse->getHeader('Upload-Offset')[0]);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        // check upload status through HEAD
        $headResponse1 = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse1->getStatusCode());
        $this->assertSame('?0', $headResponse1->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::CHUNK_SIZE, $headResponse1->getHeader('Upload-Offset')[0]);

        // upload the second, final chunk
        $chunk2 = \Safe\fopen($chunks[1], 'r');
        $finishUploadResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk2,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($finishUploadResponse);
        $this->assertSame('?1', $finishUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::FILE_SIZE, $finishUploadResponse->getHeader('Upload-Offset')[0]);

        // upload resource is gone once finished
        $headResponse2 = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(404, $headResponse2->getStatusCode());

        // verify the merged file is downloadable and matches what was uploaded
        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'application/octet-stream');
        $this->assertSame(self::FILE_SIZE, strlen((string) $downloadResponse->getBody()));

        $this->cleanupChunks($chunks);
        unlink(self::FILE_PATH);
    }
}
