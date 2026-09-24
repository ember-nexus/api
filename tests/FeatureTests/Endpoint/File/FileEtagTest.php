<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Concrete (non-wildcard) `If-None-Match` and `If-Match` values on the file endpoints of nodes and relations, see
 * WildcardFileEtagTest for the `*` variants.
 */
class FileEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string STALE_ETAG = '"staleEtagWhichNeverMatches"';

    private function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    private function sendFile(string $method, string $elementId, string $content, array $headers = []): mixed
    {
        return $this->runUploadRequest(
            $method,
            sprintf('/%s/file', $elementId),
            $content,
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $headers)
        );
    }

    private function getFileEtag(string $elementId): string
    {
        $response = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $response->getHeader('ETag'));

        return $response->getHeader('ETag')[0];
    }

    private function assertFileEtagBehaviour(string $elementId): void
    {
        $this->assertIsCreatedResponse($this->sendFile('POST', $elementId, 'first content'), false);
        $etag = $this->getFileEtag($elementId);

        // reading: matching ETag means not modified, other ETag delivers the file
        $this->assertNotModifiedResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => $etag]));
        $this->assertNotModifiedResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => self::STALE_ETAG.', '.$etag]));
        $staleRead = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => self::STALE_ETAG]);
        $this->assertSame(200, $staleRead->getStatusCode());
        $this->assertSame('first content', (string) $staleRead->getBody());

        // writing with a stale If-Match is rejected and changes nothing
        $this->assertIsProblemResponse($this->sendFile('PUT', $elementId, 'stale put', ['If-Match' => self::STALE_ETAG]), 412);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => self::STALE_ETAG]), 412);
        $this->assertSame('first content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());
        $this->assertSame($etag, $this->getFileEtag($elementId));

        // writing with the current If-Match is accepted
        $this->assertIsCreatedResponse($this->sendFile('PUT', $elementId, 'second content', ['If-Match' => $etag]), false);
        $newEtag = $this->getFileEtag($elementId);
        $this->assertNotSame($etag, $newEtag);
        $this->assertSame('second content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        // the ETag of the replaced file is stale now
        $this->assertIsProblemResponse($this->sendFile('PUT', $elementId, 'outdated put', ['If-Match' => $etag]), 412);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => $etag]), 412);
        $this->assertSame(200, $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-None-Match' => $etag])->getStatusCode());

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => $newEtag]));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);
    }

    public function testFileEtagsOnNode(): void
    {
        $nodeId = $this->createNode('file-etag-node');

        $this->assertFileEtagBehaviour($nodeId);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $nodeId), self::TOKEN));
    }

    public function testFileEtagsOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'file-etag-relation');

        $this->assertFileEtagBehaviour($relationId);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
