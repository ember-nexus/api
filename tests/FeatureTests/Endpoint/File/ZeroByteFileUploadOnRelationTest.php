<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors ZeroByteFileUploadTest, but targets a relation instead of a node.
 */
class ZeroByteFileUploadOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testZeroByteFileCanBeUploadedDirectlyAndDownloadedAgainOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'zero-byte-direct-upload-relation');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            '',
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($response, false);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertSame(200, $downloadResponse->getStatusCode());
        $this->assertSame(['0'], $downloadResponse->getHeader('Content-Length'));
        $this->assertSame('', (string) $downloadResponse->getBody());
        $this->assertSame(['application/x-empty'], $downloadResponse->getHeader('Content-Type'));

        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN));
        $this->assertSame(0, $body['file']['contentLength']);
        $this->assertSame(hash('sha256', ''), $body['file']['hash']['sha256']);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
