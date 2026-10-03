<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * `If-None-Match: *` / `If-Match: *` on the file endpoints of nodes and relations. An element without file has no
 * file representation: `If-None-Match: *` is satisfied, `If-Match: *` is not (RFC 9110).
 */
class WildcardFileEtagTest extends BaseRequestTestCase
{
    // no test asserts the index ETag of this user, unlike for the user of IfMatchTest, which other file tests share
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * @param array<string, string> $additionalHeaders
     */
    private function sendFile(string $method, string $elementId, string $content, array $additionalHeaders = []): mixed
    {
        return $this->runUploadRequest(
            $method,
            sprintf('/%s/file', $elementId),
            $content,
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $additionalHeaders)
        );
    }

    private function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    private function assertWildcardBehaviour(string $elementId): void
    {
        // no file yet: If-Match can not be satisfied, If-None-Match is
        $this->assertIsProblemResponse($this->sendFile('POST', $elementId, 'content', ['If-Match' => '*']), 412);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);
        $this->assertIsCreatedResponse($this->sendFile('POST', $elementId, 'content', ['If-None-Match' => '*']), false);

        // file exists now: If-None-Match: * fails, for reading it means 'not modified'
        $this->assertIsProblemResponse($this->sendFile('POST', $elementId, 'other', ['If-None-Match' => '*']), 412);
        $this->assertIsProblemResponse($this->sendFile('PUT', $elementId, 'other', ['If-None-Match' => '*']), 412);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => '*']), 412);
        $this->assertNotModifiedResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => '*']));
        $this->assertSame('content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        // file exists now: If-Match: * is satisfied
        $this->assertIsCreatedResponse($this->sendFile('PUT', $elementId, 'replaced', ['If-Match' => '*']), false);
        $this->assertSame('replaced', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => '*'])->getBody());
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => '*']));

        // deleted again: no representation
        $this->assertIsProblemResponse($this->sendFile('PUT', $elementId, 'again', ['If-Match' => '*']), 412);
    }

    public function testWildcardOnNodeFile(): void
    {
        $nodeId = $this->createNode('wildcard-file-etag-node');

        $this->assertWildcardBehaviour($nodeId);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $nodeId), self::TOKEN));
    }

    public function testWildcardOnRelationFile(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'wildcard-file-etag-relation');

        $this->assertWildcardBehaviour($relationId);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testDeleteFileOfElementWithoutFileReturns404(): void
    {
        $nodeId = $this->createNode('delete-file-without-file-node');
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $nodeId), self::TOKEN), 404);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $nodeId), self::TOKEN));

        $relationId = $this->createEphemeralRelation(self::TOKEN, 'delete-file-without-file-relation');
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $relationId), self::TOKEN), 404);
        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testDeleteFileTwiceReturns404TheSecondTime(): void
    {
        $nodeId = $this->createNode('delete-file-twice');
        $this->assertIsCreatedResponse($this->sendFile('POST', $nodeId, 'content'), false);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s/file', $nodeId), self::TOKEN));
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $nodeId), self::TOKEN), 404);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $nodeId), self::TOKEN));
    }
}
