<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * The filename used in a file download's `Content-Disposition` header is decided by priority: the element's
 * 'name' property, if it exists and is a string, otherwise the element's id. In both cases, the extension is
 * taken from the file's stored extension, falling back to "bin" if none was determined during upload (e.g. the
 * client did not supply a `Content-Disposition` header with a filename during upload).
 *
 * Explicitly setting the filename to the element's id is likely to be disabled in a future feature upgrade
 * (not yet decided); until then, both cases must keep working.
 */
#[Group('test')]
class FileNameFallbackTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testDownloadFilenameFallsBackToElementIdWhenNoNamePropertyIsSet(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $filePath = __DIR__.'/../../Asset/filename-fallback-no-name.bin';
        $this->generateDeterministicFile(13572468, 16, $filePath);
        $file = \Safe\fopen($filePath, 'r');

        // deliberately no Content-Disposition header on upload, so the extension also falls back to the default
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);
        unlink($filePath);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'application/octet-stream');
        $this->assertStringContainsString(
            sprintf('filename=%s.bin', $elementId),
            $downloadResponse->getHeader('Content-Disposition')[0]
        );

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testDownloadFilenameUsesExplicitNamePropertyWhenSet(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'someOtherFilename',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $filePath = __DIR__.'/../../Asset/filename-fallback-explicit-name.bin';
        $this->generateDeterministicFile(24681357, 16, $filePath);
        $file = \Safe\fopen($filePath, 'r');

        // deliberately no Content-Disposition header on upload, so the extension also falls back to the default
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);
        unlink($filePath);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'application/octet-stream');
        $this->assertStringContainsString(
            'filename=someOtherFilename.bin',
            $downloadResponse->getHeader('Content-Disposition')[0]
        );

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
