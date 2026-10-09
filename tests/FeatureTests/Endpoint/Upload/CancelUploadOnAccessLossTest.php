<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * When the owner of an upload loses UPDATE access to the target element, the upload is not cancelled immediately.
 * It persists until the owner's next HEAD or PATCH on it re-checks access on the target, cancels the upload (i.e.
 * deletes its chunks and its `Upload` element) and answers 404 - or until it expires on its own.
 */
class CancelUploadOnAccessLossTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array{0: string, 1: string} [targetId, uploadId], the upload has one stored chunk
     */
    private function createTargetWithUpload(bool $onRelation): array
    {
        $targetId = $onRelation
            ? $this->createEphemeralRelation(self::TOKEN, 'cancel-upload-on-access-loss')
            : $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, ['type' => 'Data', 'data' => ['name' => 'cancel-upload-on-access-loss']]));

        $response = $this->runUploadRequest('POST', sprintf('/%s/file', $targetId), str_repeat('a', self::CHUNK_SIZE), self::TOKEN, [
            'Upload-Complete' => '?0',
            'Content-Type' => 'application/octet-stream',
        ]);
        $this->assertNoContentResponse($response, true);
        $uploadId = $this->getUuidFromLocation($response);
        $this->assertUploadExists($uploadId, true);

        return [$targetId, $uploadId];
    }

    /**
     * @return string[] ids of the OWNS relations which grant the owner access to the nodes the target consists of
     */
    private function getOwnsRelationIds(string $targetId, bool $onRelation): array
    {
        $nodeIds = [$targetId];
        if ($onRelation) {
            $relation = $this->getBody($this->runGetRequest(sprintf('/%s', $targetId), self::TOKEN));
            $nodeIds = [$relation['start'], $relation['end']];
        }
        $relationIds = [];
        foreach ($nodeIds as $nodeId) {
            $body = $this->getBody($this->runGetRequest(sprintf('/%s/parents', $nodeId), self::TOKEN));
            $relationIds[] = $body['relations'][0]['id'];
        }

        return $relationIds;
    }

    private function assertUploadExists(string $uploadId, bool $exists): void
    {
        $result = $this->getCypherClient()->run('MATCH (u:Upload {id: $id}) RETURN count(u) AS count', ['id' => $uploadId]);
        $this->assertSame($exists ? 1 : 0, $result->first()->get('count'));

        $expectedChunkCount = $exists ? 1 : 0;
        // listing the upload bucket is eventually consistent on some S3-compatible backends (observed on
        // SeaweedFS), so a chunk that was just written (or just deleted) can briefly be missing (or lingering)
        // from a ListObjectsV2 response; poll briefly rather than asserting on the very first attempt, the same
        // way assertSearchAccess() does for Elasticsearch elsewhere in this suite
        $actualChunkCount = $expectedChunkCount;
        for ($attempt = 0; $attempt < 10; ++$attempt) {
            $actualChunkCount = $this->countUploadChunksInUploadBucket($uploadId);
            if ($actualChunkCount === $expectedChunkCount) {
                break;
            }
            usleep(300_000);
        }
        $this->assertSame($expectedChunkCount, $actualChunkCount);
    }

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    #[DataProvider('targetProvider')]
    public function testRemovingAccessThroughApiCancelsUploadOnNextRequest(bool $onRelation): void
    {
        [$targetId, $uploadId] = $this->createTargetWithUpload($onRelation);
        $ownsRelationIds = $this->getOwnsRelationIds($targetId, $onRelation);

        foreach ($ownsRelationIds as $ownsRelationId) {
            $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $ownsRelationId), self::TOKEN));
        }

        // the upload survives the access removal until the owner's next request notices it
        $this->assertUploadExists($uploadId, true);
        $this->assertSame(404, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());

        $this->assertUploadExists($uploadId, false);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN), 404);
    }

    /**
     * @return array<string, array{0: bool, 1: string}>
     */
    public static function lazyCancellationProvider(): array
    {
        $cases = [];
        foreach (self::targetProvider() as $targetName => [$onRelation]) {
            foreach (['HEAD', 'PATCH'] as $method) {
                $cases[sprintf('%s via %s', $targetName, $method)] = [$onRelation, $method];
            }
        }

        return $cases;
    }

    #[DataProvider('lazyCancellationProvider')]
    public function testUploadIsCancelledWhenAccessLossIsDetectedByRequest(bool $onRelation, string $method): void
    {
        [$targetId, $uploadId] = $this->createTargetWithUpload($onRelation);
        $ownsRelationIds = $this->getOwnsRelationIds($targetId, $onRelation);

        // no events are fired for this, so only the request can notice it
        $this->getCypherClient()->run('MATCH ()-[r:OWNS]->() WHERE r.id IN $ids DELETE r', ['ids' => $ownsRelationIds]);
        $this->assertUploadExists($uploadId, true);

        if ('HEAD' === $method) {
            $response = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
            $this->assertSame(404, $response->getStatusCode());
        } else {
            $response = $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), 'data', self::TOKEN, [
                'Upload-Complete' => '?1',
                'Upload-Offset' => self::CHUNK_SIZE,
                'Content-Type' => 'application/partial-upload',
            ]);
            $this->assertIsProblemResponse($response, 404);
        }

        $this->assertUploadExists($uploadId, false);
    }
}
