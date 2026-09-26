<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * Completing an upload with fewer or more bytes than the declared `Upload-Length` is rejected with 409 (same status as
 * the already existing "exceeds" case) and the upload, including its chunks, is discarded. A non-final chunk which
 * exceeds the declared length is rejected as well, but the upload stays. Creation rejects a first chunk which
 * exceeds the declared length.
 */
class UploadLengthEnforcementTest extends BaseRequestTestCase
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

    private function createTarget(bool $onRelation): string
    {
        if ($onRelation) {
            return $this->createEphemeralRelation(self::TOKEN, 'upload-length-enforcement');
        }

        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'upload-length-enforcement'],
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

    private function assertUploadDiscarded(string $uploadId, string $elementId): void
    {
        $this->assertSame(404, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($uploadId));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithFewerBytesThanDeclaredIsRejectedAndDiscardsUpload(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation);
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));

        $response = $this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', 99), true);
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('upload length', $this->getBody($response)['detail']);

        $this->assertUploadDiscarded($uploadId, $elementId);
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithMoreBytesThanDeclaredIsRejectedAndDiscardsUpload(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation);
        $uploadId = $this->getUuidFromLocation($this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE + 100));

        $response = $this->patchUpload($uploadId, self::MIN_CHUNK_SIZE, str_repeat('b', 101), true);
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('exceeds', $this->getBody($response)['detail']);

        $this->assertUploadDiscarded($uploadId, $elementId);
        $this->deleteTarget($onRelation, $elementId);
    }

    #[DataProvider('targetProvider')]
    public function testCompletingWithExactlyTheDeclaredLengthSucceeds(bool $onRelation): void
    {
        $elementId = $this->createTarget($onRelation);
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
        $elementId = $this->createTarget($onRelation);
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
        $elementId = $this->createTarget($onRelation);

        $response = $this->createUpload($elementId, str_repeat('a', self::MIN_CHUNK_SIZE), self::MIN_CHUNK_SIZE - 1);
        $this->assertIsProblemResponse($response, 409);

        $this->deleteTarget($onRelation, $elementId);
    }
}
