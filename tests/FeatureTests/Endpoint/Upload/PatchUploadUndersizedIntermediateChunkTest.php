<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that intermediate PATCH chunks below the minimum chunk size are rejected, as S3 requires every
 * multipart part except the last to be at least 5 MiB. The completing chunk (`Upload-Complete: ?1`) is exempt.
 */
class PatchUploadUndersizedIntermediateChunkTest extends BaseRequestTestCase
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

    /**
     * @return array{0: string, 1: int} the created upload's id, and the offset it is now at
     */
    private function createResumableUploadWithOneChunk(string $elementId, string $assetName): array
    {
        $filePath = __DIR__.'/../../Asset/'.$assetName;
        $this->generateDeterministicFile(crc32($assetName), self::CHUNK_SIZE, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
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
        $this->assertNoContentResponse($response, true);

        return [$this->getUuidFromLocation($response), (int) $response->getHeader('Upload-Offset')[0]];
    }

    public function testZeroLengthIntermediateChunkIsRejected(): void
    {
        $elementId = $this->createElement('patch-upload-undersized-zero');
        [$uploadId, $offset] = $this->createResumableUploadWithOneChunk($elementId, 'patch-upload-undersized-zero.bin');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($response, 400);

        // the rejected chunk must not have advanced the upload's offset
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) $offset, $headResponse->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
    }

    public function testUndersizedNonZeroIntermediateChunkIsRejected(): void
    {
        $elementId = $this->createElement('patch-upload-undersized-nonzero');
        [$uploadId, $offset] = $this->createResumableUploadWithOneChunk($elementId, 'patch-upload-undersized-nonzero.bin');

        $tinyChunkPath = __DIR__.'/../../Asset/patch-upload-undersized-nonzero-chunk.bin';
        $this->generateDeterministicFile(11223344, 1024, $tinyChunkPath);
        $tinyChunk = \Safe\fopen($tinyChunkPath, 'r');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $tinyChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        unlink($tinyChunkPath);
        $this->assertIsProblemResponse($response, 400);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
    }

    /**
     * A zero-length chunk which completes the upload must still be accepted.
     */
    public function testZeroLengthFinalChunkIsStillAccepted(): void
    {
        $elementId = $this->createElement('patch-upload-zero-final-chunk');
        [$uploadId, $offset] = $this->createResumableUploadWithOneChunk($elementId, 'patch-upload-zero-final-chunk.bin');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($response);
        $this->assertSame('?1', $response->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) $offset, $response->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
