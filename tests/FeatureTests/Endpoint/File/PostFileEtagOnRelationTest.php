<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors PostFileEtagTest, but targets a relation instead of a node.
 */
class PostFileEtagOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:M3WHIDj4q62EY0XiZFMLnv';

    /**
     * @param array<string, string> $additionalHeaders
     */
    private function postFile(string $relationId, array $additionalHeaders = []): mixed
    {
        $filePath = __DIR__.'/../../Asset/post-file-etag-relation.bin';
        $this->generateDeterministicFile(91827364, 64, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $additionalHeaders)
        );
        unlink($filePath);

        return $response;
    }

    public function testPostFileIsRejectedWhenIfMatchDoesNotMatchOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'post-file-etag-if-match-mismatch-relation');

        $this->assertIsProblemResponse($this->postFile($relationId, ['If-Match' => '"definitelyNotTheEtag"']), 412);

        // the precondition failed, so no file may have been created
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN), 404);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testPostFileIsRejectedWhenIfMatchCarriesTheRelationEtag(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'post-file-etag-relation-etag-does-not-apply');

        $relationEtag = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN)->getHeader('Etag')[0];

        $this->assertIsProblemResponse($this->postFile($relationId, ['If-Match' => $relationEtag]), 412);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testPostFileWithoutPreconditionHeadersStillWorksOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'post-file-etag-no-precondition-relation');

        $this->assertIsCreatedResponse($this->postFile($relationId), false);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testPostFileProceedsWhenIfNoneMatchDoesNotMatchOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'post-file-etag-if-none-match-relation');

        $this->assertIsCreatedResponse($this->postFile($relationId, ['If-None-Match' => '"definitelyNotTheEtag"']), false);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
