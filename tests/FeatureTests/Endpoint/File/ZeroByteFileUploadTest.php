<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * A zero-length file is a legitimate file (e.g. a placeholder, a "touch"ed marker, an intentionally emptied
 * document) - nothing in the upload/download path requires non-empty content, so this must be supported, not
 * rejected. In particular, MIME type sniffing (`MimeTypeService::getMimeTypeFromResource()`) must not crash on
 * empty content: `finfo::buffer('')` returns the well-defined 'application/x-empty' rather than failing, so no
 * special-cased "null mime type" is needed for this.
 */
#[Group('test')]
class ZeroByteFileUploadTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createElement(string $name): string
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name,
                ],
            ]
        );

        return $this->getUuidFromLocation($response);
    }

    public function testZeroByteFileCanBeUploadedDirectlyAndDownloadedAgain(): void
    {
        $elementId = $this->createElement('zero-byte-direct-upload');

        // no 'Upload-Complete' header at all -> a direct, non-resumable upload of the (empty) request body
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($response, false);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $downloadResponse->getStatusCode());
        $this->assertSame(['0'], $downloadResponse->getHeader('Content-Length'));
        $this->assertSame('', (string) $downloadResponse->getBody());
        $this->assertSame(['application/x-empty'], $downloadResponse->getHeader('Content-Type'));

        $elementResponse = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = $this->getBody($elementResponse);
        $this->assertSame(0, $body['file']['contentLength']);
        $this->assertSame(64, strlen($body['file']['hash']['sha256']));
        $this->assertSame(hash('sha256', ''), $body['file']['hash']['sha256']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
