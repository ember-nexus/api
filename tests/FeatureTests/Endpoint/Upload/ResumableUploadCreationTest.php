<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The resumable upload draft standard (the basis for this API's `POST`/`PATCH` upload endpoints) allows a
 * resumable upload to be created either with an initial chunk of data, or with none at all - an empty body
 * alongside `Upload-Complete: ?0`, which merely reserves the upload without transferring any bytes yet. This is
 * explicitly permitted unless a server opts out of it; this API does not, so both forms are covered here.
 */
#[Group('test')]
class ResumableUploadCreationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    private function createElement(string $name): string
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name,
                ],
            ]
        );

        return $this->getUuidFromLocation($response);
    }

    public function testResumableUploadCreationWithInitialData(): void
    {
        $elementId = $this->createElement('resumable-upload-creation-with-data');

        $filePath = __DIR__.'/../../Asset/resumable-upload-creation-with-data.bin';
        $this->generateDeterministicFile(19283746, self::CHUNK_SIZE, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        unlink($filePath);

        $this->assertNoContentResponse($createUploadResponse, true);
        $this->assertSame('?0', $createUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::CHUNK_SIZE, $createUploadResponse->getHeader('Upload-Offset')[0]);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        // the initial chunk was actually received and persisted, not just acknowledged
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
    }

    public function testResumableUploadCreationWithoutInitialData(): void
    {
        $elementId = $this->createElement('resumable-upload-creation-without-data');

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );

        $this->assertNoContentResponse($createUploadResponse, true);
        $this->assertSame('?0', $createUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame('0', $createUploadResponse->getHeader('Upload-Offset')[0]);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        // the upload session exists and expects its first chunk at offset 0, even though no data was received yet
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame('0', $headResponse->getHeader('Upload-Offset')[0]);

        // an empty-data creation is a genuinely usable upload session, not just an accepted-but-broken stub: the
        // whole file can still be appended and completed from offset 0 via PATCH
        $filePath = __DIR__.'/../../Asset/resumable-upload-creation-without-data.bin';
        $this->generateDeterministicFile(56473829, self::CHUNK_SIZE, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $finishUploadResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($finishUploadResponse);
        $this->assertSame('?1', $finishUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::CHUNK_SIZE, $finishUploadResponse->getHeader('Upload-Offset')[0]);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertSame(self::CHUNK_SIZE, strlen((string) $downloadResponse->getBody()));

        unlink($filePath);
    }
}
