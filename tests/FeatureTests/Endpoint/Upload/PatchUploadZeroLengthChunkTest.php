<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * A non-final chunk of exactly 0 bytes is a no-op which reports the current offset (usable as status check): nothing
 * is stored and neither offset nor hash state change. Chunks of 1 to minimum - 1 bytes stay rejected on non-final
 * requests, as S3 does not accept small parts. The same rules apply when the upload is created.
 */
class PatchUploadZeroLengthChunkTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int MIN_CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    /**
     * @return array<string, array{0: bool, 1: int, 2: bool}> onRelation, chunk size, accepted
     */
    public static function chunkSizeProvider(): array
    {
        $cases = [];
        foreach (self::targetProvider() as $targetName => [$onRelation]) {
            $cases[$targetName.', 0 bytes'] = [$onRelation, 0, true];
            $cases[$targetName.', 1 byte'] = [$onRelation, 1, false];
            $cases[$targetName.', minimum - 1 bytes'] = [$onRelation, self::MIN_CHUNK_SIZE - 1, false];
            $cases[$targetName.', minimum bytes'] = [$onRelation, self::MIN_CHUNK_SIZE, true];
        }

        return $cases;
    }

    private function createTarget(bool $onRelation, string $name): string
    {
        if ($onRelation) {
            return $this->createEphemeralRelation(self::TOKEN, $name);
        }

        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    private function deleteTarget(bool $onRelation, string $elementId): void
    {
        if ($onRelation) {
            $this->deleteEphemeralRelation(self::TOKEN, $elementId);
        } else {
            $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
        }
    }

    private function createUpload(string $elementId, string $body): ResponseInterface
    {
        return $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $body,
            self::TOKEN,
            ['Upload-Complete' => '?0', 'Content-Type' => 'application/octet-stream']
        );
    }

    private function patchUpload(string $uploadId, int $offset, string $body, bool $complete = false, ?string $digest = null): ResponseInterface
    {
        $headers = [
            'Upload-Complete' => $complete ? '?1' : '?0',
            'Upload-Offset' => $offset,
            'Content-Type' => 'application/partial-upload',
        ];
        if (null !== $digest) {
            $headers['Repr-Digest'] = sprintf('sha-256=:%s:', base64_encode(\Safe\hex2bin($digest)));
        }

        return $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), $body, self::TOKEN, $headers);
    }

    private function assertOffset(ResponseInterface $response, int $offset): void
    {
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame((string) $offset, $response->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $response->getHeader('Upload-Complete')[0]);
    }

    #[DataProvider('chunkSizeProvider')]
    public function testChunkSizesOnCreation(bool $onRelation, int $size, bool $accepted): void
    {
        $elementId = $this->createTarget($onRelation, 'zero-length-chunk-creation');

        $response = $this->createUpload($elementId, str_repeat('a', $size));
        if (!$accepted) {
            $this->assertIsProblemResponse($response, 400);
            $this->deleteTarget($onRelation, $elementId);

            return;
        }
        $this->assertNoContentResponse($response, true);
        $uploadId = $this->getUuidFromLocation($response);
        $this->assertOffset($response, $size);
        $this->assertOffset($this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN), $size);
        // an empty first chunk creates the upload only
        $this->assertSame(0 === $size ? 0 : 1, $this->countUploadChunksInUploadBucket($uploadId));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN));
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('chunkSizeProvider')]
    public function testChunkSizesOnPatch(bool $onRelation, int $size, bool $accepted): void
    {
        $elementId = $this->createTarget($onRelation, 'zero-length-chunk-patch');
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, ''));

        $response = $this->patchUpload($uploadId, 0, str_repeat('b', $size));
        if ($accepted) {
            $this->assertOffset($response, $size);
        } else {
            $this->assertIsProblemResponse($response, 400);
        }
        $expectedOffset = $accepted ? $size : 0;
        $this->assertOffset($this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN), $expectedOffset);
        $this->assertSame($accepted && $size > 0 ? 1 : 0, $this->countUploadChunksInUploadBucket($uploadId));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN));
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testEmptyChunksDoNotAlterOffsetHashStateOrChunksAndUploadStaysCompletable(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'zero-length-chunk-lifecycle');
        $firstChunk = str_repeat('c', self::MIN_CHUNK_SIZE);
        $finalChunk = 'the final chunk';

        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, $firstChunk));

        // empty chunks as status check, repeatedly and between real chunks
        $this->assertOffset($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, ''), self::MIN_CHUNK_SIZE);
        $this->assertOffset($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, ''), self::MIN_CHUNK_SIZE);
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));

        // the offset of an empty chunk must still match
        $this->assertIsProblemResponse($this->patchUpload($uploadId, 0, ''), 409);

        $secondChunk = str_repeat('d', self::MIN_CHUNK_SIZE);
        $expectedDigest = hash('sha256', $firstChunk.$secondChunk.$finalChunk);
        $this->assertOffset($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, $secondChunk), 2 * self::MIN_CHUNK_SIZE);
        $this->assertOffset($this->patchUpload($uploadId, 2 * self::MIN_CHUNK_SIZE, ''), 2 * self::MIN_CHUNK_SIZE);
        $this->assertSame(2, $this->countUploadChunksInUploadBucket($uploadId));

        // the hash state was not altered by the empty chunks, so the digest of the whole file still verifies
        $this->assertNoContentResponse($this->patchUpload($uploadId, 2 * self::MIN_CHUNK_SIZE, $finalChunk, true, $expectedDigest));

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $fileResponse->getStatusCode());
        $this->assertSame($expectedDigest, hash('sha256', (string) $fileResponse->getBody()));
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($uploadId));

        $this->deleteTarget($onRelation, $elementId);
    }
}
