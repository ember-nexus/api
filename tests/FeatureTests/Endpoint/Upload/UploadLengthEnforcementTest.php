<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Completing an upload with fewer or more bytes than the declared `Upload-Length` is rejected with 409 (same status as
 * the already existing "exceeds" case). Only the request is rejected, the upload keeps its state and can be completed
 * by a corrected request. A non-final chunk which exceeds the declared length is rejected the same way. Creation
 * rejects a first chunk which exceeds the declared length.
 */
class UploadLengthEnforcementTest extends BaseUploadTestCase
{
    private const int MIN_CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    private function createUpload(string $elementId, string $body, int $uploadLength): ResponseInterface
    {
        return $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $body,
            self::TOKEN,
            ['Upload-Complete' => '?0', 'Upload-Length' => $uploadLength, 'Content-Type' => 'application/octet-stream']
        );
    }

    private function patchUpload(string $uploadId, int $offset, string $body, bool $complete): ResponseInterface
    {
        return $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), $body, self::TOKEN, [
            'Upload-Complete' => $complete ? '?1' : '?0',
            'Upload-Offset' => $offset,
            'Content-Type' => 'application/partial-upload',
        ]);
    }

    private function assertUploadKept(string $uploadId, string $elementId): void
    {
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) self::MIN_CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        // the rejected chunk was removed, only the first chunk remains
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);
    }

    private function assertUploadCanBeCompleted(string $uploadId, string $elementId, int $missingBytes): void
    {
        $this->assertNoContentResponse($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', $missingBytes), true));

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $fileResponse->getStatusCode());
        $this->assertSame(self::MIN_CHUNK_SIZE + $missingBytes, strlen((string) $fileResponse->getBody()));
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithFewerBytesThanDeclaredIsRejectedButKeepsUpload(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'upload-length-enforcement');
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));

        $response = $this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', 99), true);
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('upload length', $this->getBody($response)['detail']);

        $this->assertUploadKept($uploadId, $elementId);
        // the upload was declared with 100 more bytes, so the corrected request completes it
        $this->assertUploadCanBeCompleted($uploadId, $elementId, 100);
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithMoreBytesThanDeclaredIsRejectedButKeepsUpload(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'upload-length-enforcement');
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));

        $response = $this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', 101), true);
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('exceeds', $this->getBody($response)['detail']);

        $this->assertUploadKept($uploadId, $elementId);
        $this->assertUploadCanBeCompleted($uploadId, $elementId, 100);
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithExactlyTheDeclaredLengthSucceeds(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'upload-length-enforcement');
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));

        $this->assertNoContentResponse($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', 100), true));

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $fileResponse->getStatusCode());
        $this->assertSame(self::MIN_CHUNK_SIZE + 100, strlen((string) $fileResponse->getBody()));
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testNonFinalChunkExceedingDeclaredLengthIsRejectedButKeepsUpload(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'upload-length-enforcement');
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));

        $this->assertIsProblemResponse($this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', self::MIN_CHUNK_SIZE), false), 409);

        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) self::MIN_CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN));
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testFirstChunkExceedingDeclaredLengthIsRejectedOnCreation(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation, 'upload-length-enforcement');

        $response = $this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE - 1);
        $this->assertIsProblemResponse($response, 409);

        $this->deleteTarget($onRelation, $elementId);
    }
}
