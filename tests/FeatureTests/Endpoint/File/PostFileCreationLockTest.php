<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Factory\Type\RedisKeyFactory;
use App\Tests\FeatureTests\BaseRequestTestCase;
use Predis\Client as RedisClient;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

/**
 * `POST /<id>/file` creates the file: it reserves the element in Redis (`file:create:<id>`) while it runs and fails if
 * the element has an upload in progress. `PUT /<id>/file` does not take the lock, but respects it.
 */
class PostFileCreationLockTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int MIN_CHUNK_SIZE = 5 * 1024 * 1024;

    private function createElement(): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'post-file-creation-lock'],
        ]));
    }

    private function deleteElement(string $elementId): void
    {
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    private function getRedis(): RedisClient
    {
        return new RedisClient($_ENV['REDIS_AUTH']);
    }

    private function lockKey(string $elementId): string
    {
        return (string) (new RedisKeyFactory())->getFileCreationLockRedisKey(Uuid::fromString($elementId));
    }

    /**
     * @param array<string, string|int> $headers
     */
    private function upload(string $method, string $elementId, string $body, array $headers = []): ResponseInterface
    {
        return $this->runUploadRequest($method, sprintf('/%s/file', $elementId), $body, self::TOKEN, $headers);
    }

    public function testPostWhileCreationLockIsHeldFails(): void
    {
        $elementId = $this->createElement();
        $redis = $this->getRedis();
        $redis->set($this->lockKey($elementId), 'other-request', 'PX', 60000);

        $response = $this->upload('POST', $elementId, 'some content');
        $this->assertIsProblemResponse($response, 409);
        // the lock of the other request is untouched
        $this->assertSame('other-request', $redis->get($this->lockKey($elementId)));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $redis->del([$this->lockKey($elementId)]);
        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);

        $this->deleteElement($elementId);
    }

    public function testPostReleasesLockAfterSuccess(): void
    {
        $elementId = $this->createElement();

        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));

        $this->deleteElement($elementId);
    }

    public function testPostReleasesLockAfterFailedRequest(): void
    {
        $elementId = $this->createElement();

        $wrongDigest = sprintf('sha-256=:%s:', base64_encode(str_repeat('a', 32)));
        $response = $this->upload('POST', $elementId, 'some content', ['Repr-Digest' => $wrongDigest]);
        $this->assertIsProblemResponse($response, 400);

        // no waiting for the lock to expire
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));
        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);

        $this->deleteElement($elementId);
    }

    public function testPostReleasesLockAfterElementAlreadyHasFile(): void
    {
        $elementId = $this->createElement();
        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);

        $this->assertIsProblemResponse($this->upload('POST', $elementId, 'other content'), 409);
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));

        $this->deleteElement($elementId);
    }

    public function testPostWhileUploadTargetsElementFails(): void
    {
        $elementId = $this->createElement();

        $createResponse = $this->upload('POST', $elementId, str_repeat('a', self::MIN_CHUNK_SIZE), [
            'Upload-Complete' => '?0',
            'Upload-Length' => self::MIN_CHUNK_SIZE * 2,
            'Content-Type' => 'application/octet-stream',
        ]);
        $this->assertSame(204, $createResponse->getStatusCode());
        $uploadId = $this->getUuidFromLocation($createResponse);
        // creating the upload does not leave a lock behind
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));

        $response = $this->upload('POST', $elementId, 'some content');
        $this->assertIsProblemResponse($response, 409);
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN));
        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);

        $this->deleteElement($elementId);
    }

    public function testPutWhileCreationLockIsHeldFails(): void
    {
        $elementId = $this->createElement();
        $redis = $this->getRedis();
        $redis->set($this->lockKey($elementId), 'other-request', 'PX', 60000);

        $this->assertIsProblemResponse($this->upload('PUT', $elementId, 'some content'), 409);
        // PUT never takes or removes the lock
        $this->assertSame('other-request', $redis->get($this->lockKey($elementId)));

        $redis->del([$this->lockKey($elementId)]);
        $this->assertIsCreatedResponse($this->upload('PUT', $elementId, 'some content'), false);

        $this->deleteElement($elementId);
    }

    public function testPutReplacesExistingFileWithoutLock(): void
    {
        $elementId = $this->createElement();
        $this->assertIsCreatedResponse($this->upload('POST', $elementId, 'some content'), false);

        $response = $this->upload('PUT', $elementId, 'replaced content');
        $this->assertContains($response->getStatusCode(), [200, 201, 204]);
        $this->assertSame('replaced content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());
        $this->assertSame(0, $this->getRedis()->exists($this->lockKey($elementId)));

        $this->deleteElement($elementId);
    }
}
