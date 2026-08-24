<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Complements PatchUploadUndersizedIntermediateChunkTest: unlike the minimum chunk size, which is waived for the
 * chunk that completes the upload, the maximum chunk size is not waived for anything - a single PATCH request is
 * still a single S3 multipart part, which has a hard upper size limit regardless of whether it happens to be the
 * last one. The configured limit is read from the `Upload-Limit` response header (`max-append-size`) rather than
 * hardcoded, so this stays correct if the configuration changes.
 */
#[Group('test')]
class PatchUploadOversizedChunkTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FIRST_CHUNK_SIZE = 5 * 1024 * 1024;

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

    private function getMaxAppendSizeInBytes(string $uploadLimitHeaderValue): int
    {
        $this->assertMatchesRegularExpression('/max-append-size=(\d+)/', $uploadLimitHeaderValue);
        preg_match('/max-append-size=(\d+)/', $uploadLimitHeaderValue, $matches);

        return (int) $matches[1];
    }

    public function testOversizedFinalChunkIsRejected(): void
    {
        $elementId = $this->createElement('patch-upload-oversized-chunk');

        $firstChunkPath = __DIR__.'/../../Asset/patch-upload-oversized-chunk-first.bin';
        $this->generateDeterministicFile(19283746, self::FIRST_CHUNK_SIZE, $firstChunkPath);
        $firstChunk = \Safe\fopen($firstChunkPath, 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        unlink($firstChunkPath);
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $maxAppendSizeInBytes = $this->getMaxAppendSizeInBytes($createUploadResponse->getHeader('Upload-Limit')[0]);

        $oversizedChunkPath = __DIR__.'/../../Asset/patch-upload-oversized-chunk-second.bin';
        $this->generateDeterministicFile(56473829, $maxAppendSizeInBytes + 1, $oversizedChunkPath);
        $oversizedChunk = \Safe\fopen($oversizedChunkPath, 'r');
        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $oversizedChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::FIRST_CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        unlink($oversizedChunkPath);
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertStringContainsString((string) $maxAppendSizeInBytes, $body['detail']);

        // the rejected chunk must not have advanced the upload's offset, nor marked it complete
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) self::FIRST_CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
    }
}
