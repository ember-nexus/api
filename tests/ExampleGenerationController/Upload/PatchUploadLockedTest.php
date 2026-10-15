<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Upload;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;
use Predis\Client as RedisClient;

/**
 * Simulates another request already holding the per-upload lock by setting its Redis key directly, instead of
 * racing two real concurrent requests (which would make the recorded example non-deterministic).
 */
class PatchUploadLockedTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';

    public function testPatchUploadFailure409Locked(): void
    {
        $elementId = 'b7c8d9e0-1f2a-4b3c-8d4e-5f6a7b8c9d0e';
        $this->createDocumentedElement($elementId, 'patch-upload-409-locked', self::TOKEN);

        $createResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createResponse, true);
        $uploadId = $this->getUuidFromLocation($createResponse);

        $redisClient = new RedisClient($_ENV['REDIS_AUTH']);
        $lockKey = sprintf('lock:upload:%s', $uploadId);
        $redisClient->set($lockKey, 'held-by-another-request', 'PX', 10000, 'NX');

        try {
            $response = $this->runUploadRequest(
                'PATCH',
                sprintf('/upload/%s', $uploadId),
                '',
                self::TOKEN,
                [
                    'Upload-Complete' => '?1',
                    'Upload-Offset' => 0,
                    'Content-Type' => 'application/partial-upload',
                ]
            );
            $this->assertIsProblemResponse($response, 409);

            $documentationHeadersPath = 'tests/recordings/api-endpoints/upload/patch-upload/409-locked-response-header.txt';
            $documentationBodyPath = 'tests/recordings/api-endpoints/upload/patch-upload/409-locked-response-body.json';
            $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
                self::PATH_TO_ROOT,
                $documentationHeadersPath,
                $response
            );
            $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
                self::PATH_TO_ROOT,
                $documentationBodyPath,
                $response
            );
        } finally {
            $redisClient->del([$lockKey]);
        }
    }
}
