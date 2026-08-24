<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The resumable upload draft standard does not itself mandate a minimum chunk size - that is a storage-backend
 * concern (S3 requires every part of a multipart upload except the last to be at least 5 MiB). This API already
 * enforces its configured minimum on an upload's very first chunk (see UploadCreationService); the same has to
 * hold for every later chunk PATCHed onto an already-started upload, or a client could pad out an upload
 * indefinitely with tiny (including zero-length) chunks that never make progress, without limit.
 *
 * The one exception, both here and for the very first chunk, is the chunk which completes the upload
 * (`Upload-Complete: ?1`): S3's minimum part size rule is explicitly waived for the last part, and a client
 * legitimately needs to be able to close out an upload with no additional data (see ZeroByteResumableUploadTest,
 * ResumableUploadCreationTest) - so only intermediate (still-incomplete) chunks are size-checked here.
 */
#[Group('test')]
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
     * Sanity check for the exemption itself: a zero-length chunk which *completes* the upload must still be
     * accepted (already covered end-to-end by ZeroByteResumableUploadTest and ResumableUploadCreationTest, but
     * checked directly against this validation here too).
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
