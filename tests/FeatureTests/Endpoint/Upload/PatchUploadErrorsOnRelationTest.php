<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Mirrors the PATCH error cases of PatchUploadIncorrectOffsetTest, PatchUploadOversizedChunkTest and
 * PatchUploadUndersizedIntermediateChunkTest, but for an upload targeting a relation instead of a node.
 */
#[Group('test')]
class PatchUploadErrorsOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array{0: string, 1: int, 2: string} the created upload's id, the offset it is now at, and the
     *                                             upload's Upload-Limit header
     */
    private function createResumableUploadWithOneChunk(string $relationId, string $assetName): array
    {
        $filePath = __DIR__.'/../../Asset/'.$assetName;
        $this->generateDeterministicFile(crc32($assetName), self::CHUNK_SIZE, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        unlink($filePath);
        $this->assertNoContentResponse($response, true);

        return [
            $this->getUuidFromLocation($response),
            (int) $response->getHeader('Upload-Offset')[0],
            $response->getHeader('Upload-Limit')[0],
        ];
    }

    private function assertUploadOffset(string $uploadId, int $expectedOffset): void
    {
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame((string) $expectedOffset, $headResponse->getHeader('Upload-Offset')[0]);
    }

    public function testPatchUploadWithIncorrectOffsetReturnsConflictOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'patch-upload-incorrect-offset-relation');
        [$uploadId, $offset] = $this->createResumableUploadWithOneChunk($relationId, 'patch-upload-incorrect-offset-relation.bin');

        $incorrectOffset = $offset + 1;
        $chunkPath = __DIR__.'/../../Asset/patch-upload-incorrect-offset-relation-chunk.bin';
        $this->generateDeterministicFile(35353535, 1024, $chunkPath);
        $chunk = \Safe\fopen($chunkPath, 'r');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $incorrectOffset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        unlink($chunkPath);
        $this->assertIsProblemResponse($response, 409);
        $body = $this->getBody($response);
        $this->assertSame($offset, $body['expected-offset']);
        $this->assertSame($incorrectOffset, $body['provided-offset']);

        $this->assertUploadOffset($uploadId, $offset);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN), 404);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testOversizedFinalChunkIsRejectedOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'patch-upload-oversized-chunk-relation');
        [$uploadId, $offset, $uploadLimit] = $this->createResumableUploadWithOneChunk($relationId, 'patch-upload-oversized-chunk-relation.bin');

        $this->assertMatchesRegularExpression('/max-append-size=(\d+)/', $uploadLimit);
        preg_match('/max-append-size=(\d+)/', $uploadLimit, $matches);
        $maxAppendSizeInBytes = (int) $matches[1];

        $chunkPath = __DIR__.'/../../Asset/patch-upload-oversized-chunk-relation-second.bin';
        $this->generateDeterministicFile(65746352, $maxAppendSizeInBytes + 1, $chunkPath);
        $chunk = \Safe\fopen($chunkPath, 'r');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        unlink($chunkPath);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString((string) $maxAppendSizeInBytes, $this->getBody($response)['detail']);

        $this->assertUploadOffset($uploadId, $offset);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN), 404);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testUndersizedIntermediateChunkIsRejectedOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'patch-upload-undersized-relation');
        [$uploadId, $offset] = $this->createResumableUploadWithOneChunk($relationId, 'patch-upload-undersized-relation.bin');

        $chunkPath = __DIR__.'/../../Asset/patch-upload-undersized-relation-chunk.bin';
        $this->generateDeterministicFile(44332211, 1024, $chunkPath);
        $chunk = \Safe\fopen($chunkPath, 'r');

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $chunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        unlink($chunkPath);
        $this->assertIsProblemResponse($response, 400);

        $this->assertUploadOffset($uploadId, $offset);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
