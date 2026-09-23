<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that a zero-length file can be created through the resumable upload flow: an empty creation request
 * followed by an empty completing PATCH. Unlike ZeroByteFileUploadTest, this goes through the S3 multipart merge
 * path of `PatchUploadController::createFile()`.
 */
class ZeroByteResumableUploadTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testZeroByteFileCanBeUploadedAcrossTwoResumableRequests(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'zero-byte-resumable-upload',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $this->assertSame('?0', $createUploadResponse->getHeader('Upload-Complete')[0]);
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

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $downloadResponse->getStatusCode());
        $this->assertSame(['0'], $downloadResponse->getHeader('Content-Length'));
        $this->assertSame('', (string) $downloadResponse->getBody());
        $this->assertSame(['application/x-empty'], $downloadResponse->getHeader('Content-Type'));

        $elementResponseAfter = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = $this->getBody($elementResponseAfter);
        $this->assertSame(0, $body['file']['contentLength']);
        $this->assertSame(hash('sha256', ''), $body['file']['hash']['sha256']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
