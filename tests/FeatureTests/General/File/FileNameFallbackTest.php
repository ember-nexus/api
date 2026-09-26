<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies the download filename in `Content-Disposition`: the element's string 'name' property if set, otherwise
 * the element's id, with the stored extension. A missing extension property falls back to "bin", while an empty extension (uploaded filename
 * without any extension, e.g. 'Makefile') means that the file has no extension at all: no suffix and no trailing dot
 * (see ElementService::getFileName()).
 */
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
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
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
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertStringContainsString(
            'filename=someOtherFilename.bin',
            $downloadResponse->getHeader('Content-Disposition')[0]
        );

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    private function assertDownloadFilename(string $expectedFileName, string $contentDisposition): void
    {
        $this->assertSame(1, preg_match('/filename=([^;]*)/', $contentDisposition, $matches), $contentDisposition);
        $this->assertSame($expectedFileName, $matches[1]);
    }

    public function testFileWithoutExtensionIsDownloadedWithoutExtension(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Makefile',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            'just some plain text',
            self::TOKEN,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=Makefile',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertDownloadFilename('Makefile', $downloadResponse->getHeader('Content-Disposition')[0]);
        $this->assertSame('just some plain text', (string) $downloadResponse->getBody());

        // an empty extension is stored as it is, it must not be replaced by the default extension
        $elementAfterUpload = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN));
        $this->assertSame('', $elementAfterUpload['file']['extension']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testReplacingFileBetweenExtensionAndNoExtensionServesOnlyTheNewFile(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'Dockerfile',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            'FROM alpine',
            self::TOKEN,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=Dockerfile',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);

        $withExtensionResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            'plain text',
            self::TOKEN,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=notes.txt',
            ]
        );
        $this->assertIsCreatedResponse($withExtensionResponse, false);
        $download = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertDownloadFilename('Dockerfile.txt', $download->getHeader('Content-Disposition')[0]);
        $this->assertSame('plain text', (string) $download->getBody());

        $withoutExtensionResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            'FROM scratch',
            self::TOKEN,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=Dockerfile',
            ]
        );
        $this->assertIsCreatedResponse($withoutExtensionResponse, false);
        $download = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertDownloadFilename('Dockerfile', $download->getHeader('Content-Disposition')[0]);
        $this->assertSame('FROM scratch', (string) $download->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testResumableUploadWithoutExtensionIsDownloadedWithoutExtension(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'LICENSE',
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
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=LICENSE',
                'Upload-Complete' => '?0',
            ]
        );
        $this->assertSame(204, $createUploadResponse->getStatusCode());
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $finishUploadResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            'MIT',
            self::TOKEN,
            [
                'Content-Type' => 'application/partial-upload',
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
            ]
        );
        $this->assertSame(204, $finishUploadResponse->getStatusCode());

        $download = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertDownloadFilename('LICENSE', $download->getHeader('Content-Disposition')[0]);
        $this->assertSame('MIT', (string) $download->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
