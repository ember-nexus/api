<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors ZeroByteResumableUploadTest, but targets a relation instead of a node.
 */
class ZeroByteResumableUploadOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testZeroByteFileCanBeUploadedAcrossTwoResumableRequestsOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'zero-byte-resumable-upload-relation');

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $this->assertSame('0', $createUploadResponse->getHeader('Upload-Offset')[0]);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $finishUploadResponse = $this->runUploadRequest(
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
        $this->assertNoContentResponse($finishUploadResponse);
        $this->assertSame('?1', $finishUploadResponse->getHeader('Upload-Complete')[0]);
        $this->assertSame('0', $finishUploadResponse->getHeader('Upload-Offset')[0]);

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
