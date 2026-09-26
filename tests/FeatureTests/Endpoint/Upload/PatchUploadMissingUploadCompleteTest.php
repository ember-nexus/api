<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * PATCH requests must declare whether they complete the upload; without the `Upload-Complete` header the request is
 * rejected and the upload stays untouched.
 */
class PatchUploadMissingUploadCompleteTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;
    private const string FILE_PATH = __DIR__.'/../../Asset/patch-upload-missing-upload-complete.bin';

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    #[DataProvider('targetProvider')]
    public function testPatchWithoutUploadCompleteHeaderIsRejected(bool $onRelation): void
    {
        if ($onRelation) {
            $elementId = $this->createEphemeralRelation(self::TOKEN, 'patch-missing-upload-complete');
        } else {
            $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
                'type' => 'Data',
                'data' => ['name' => 'patch-missing-upload-complete'],
            ]));
        }

        $this->generateDeterministicFile(80020001, self::CHUNK_SIZE + 1024, self::FILE_PATH);
        $chunks = $this->splitFileToChunks(self::FILE_PATH, self::CHUNK_SIZE);

        $createResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            \Safe\fopen($chunks[0], 'r'),
            self::TOKEN,
            ['Upload-Complete' => '?0', 'Content-Type' => 'application/octet-stream']
        );
        $this->assertNoContentResponse($createResponse, true);
        $uploadId = $this->getUuidFromLocation($createResponse);

        $patchResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            \Safe\fopen($chunks[1], 'r'),
            self::TOKEN,
            ['Upload-Offset' => self::CHUNK_SIZE, 'Content-Type' => 'application/partial-upload']
        );
        $this->assertIsProblemResponse($patchResponse, 400);

        // the upload is unchanged and can still be completed properly
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) self::CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $finishResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            \Safe\fopen($chunks[1], 'r'),
            self::TOKEN,
            ['Upload-Complete' => '?1', 'Upload-Offset' => self::CHUNK_SIZE, 'Content-Type' => 'application/partial-upload']
        );
        $this->assertNoContentResponse($finishResponse);

        $this->cleanupChunks($chunks);
        unlink(self::FILE_PATH);
        if ($onRelation) {
            $this->deleteEphemeralRelation(self::TOKEN, $elementId);
        } else {
            $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        }
    }
}
