<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors ResumableUploadLifecycleTest, but targets a relation instead of a node.
 */
class ResumableUploadLifecycleOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 24681012;
    private const int CHUNK_SIZE = 5 * 1024 * 1024;
    private const int FILE_SIZE = 2 * self::CHUNK_SIZE;
    private const string FILE_PATH = __DIR__.'/../../Asset/resumable-upload-lifecycle-relation.bin';

    public function testResumableUploadLifecycleOnRelation(): void
    {
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, self::FILE_PATH);
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::CHUNK_SIZE);

        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'resumable-upload-lifecycle-relation-start',
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
                    'name' => 'resumable-upload-lifecycle-relation-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'resumable-upload-lifecycle-relation',
                ],
            ]
        );
        $relationId = $this->getUuidFromLocation($relation);

        // create the upload with the first chunk
        $chunk1 = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $chunk1,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Disposition' => 'inline; filename=resumable-upload-lifecycle-relation.bin',
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
        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertSame(self::FILE_SIZE, strlen((string) $downloadResponse->getBody()));

        $this->cleanupChunks($chunks);
        unlink(self::FILE_PATH);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
    }
}
