<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class MultiPartUploadLifecycleTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FILE_SEED = 43038489;
    private const int FILE_SIZE = 23 * 1024 * 1024;
    private const int CHUNK_SIZE = 10 * 1024 * 1024;
    private const string FILE_PATH = __DIR__.'/../../Asset/23MB.txt';

    public function testMultiPartUploadLifecycle(): void
    {
        // create big file to be uploaded
        $this->generateDeterministicFile(self::FILE_SEED, self::FILE_SIZE, self::FILE_PATH);
        // split big file into chunks
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::CHUNK_SIZE);

        // create new node for file upload
        $postNodeResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-test',
                ],
            ]
        );
        $elementId = substr($postNodeResponse->getHeader('Location')[0], 1);

        // verify that node does not have file property
        $getNodeResponse1 = $this->runGetRequest(
            sprintf('/%s', $elementId),
            self::TOKEN
        );
        $getNodeResponseData1 = json_decode((string) $getNodeResponse1->getBody(), true);
        $this->assertArrayNotHasKey('file', $getNodeResponseData1);

        // verify that accessing file results in 404 error
        $getFileResponse1 = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsProblemResponse($getFileResponse1, 404);

        // create new resumable upload ---------------------------------------------------------------------------------
        $chunk1 = \Safe\fopen($chunks[0], 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $chunk1,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Disposition' => 'inline; filename=file.txt',
                'Content-Type' => 'text/plain',
            ]
        );
        $this->assertNoContentResponse($postFileResponse, true);
        $uploadId = substr($postFileResponse->getHeader('Location')[0], 8);

        // upload chunk 2 ----------------------------------------------------------------------------------------------
        $chunk2 = \Safe\fopen($chunks[1], 'r');
        $patchChunkResponse2 = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk2,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Upload-Offset' => 10 * 1024 * 1024,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($patchChunkResponse2);
        $this->assertSame('?0', $patchChunkResponse2->getHeader('Upload-Complete')[0]);

        // upload chunk 3 ----------------------------------------------------------------------------------------------
        $chunk3 = \Safe\fopen($chunks[2], 'r');
        $patchChunkResponse3 = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk3,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 20 * 1024 * 1024,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($patchChunkResponse3);
        $this->assertSame('?1', $patchChunkResponse3->getHeader('Upload-Complete')[0]);

        // verify that node now does have file property, and etag is different
        $getNodeResponse2 = $this->runGetRequest(
            sprintf('/%s', $elementId),
            self::TOKEN
        );
        $getNodeResponseData2 = json_decode((string) $getNodeResponse2->getBody(), true);
        $this->assertNotSame($getNodeResponse1->getHeader('ETag'), $getNodeResponse2->getHeader('ETag'));
        $this->assertArrayHasKey('file', $getNodeResponseData2);
        $this->assertSame(
            [
                'contentLength' => 24117248,
                'extension' => 'txt',
                'mimeType' => 'text/plain',
                'hashAlgorithm' => 'sha256',
                'hash' => hash_file('sha256', self::FILE_PATH),
            ],
            $getNodeResponseData2['file']
        );

        // verify that file can be downloaded
        $getFileResponse2 = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN
        );
        $this->assertIsBinaryStreamResponse($getFileResponse2, 'application/octet-stream');

        $this->cleanupChunks($chunks);
    }
}
