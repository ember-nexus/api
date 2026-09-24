<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * A file's storage key includes its extension (see FileService::getStorageBucketKey()), so replacing a file with
 * one of a different extension writes to a new key. Verifies that only the new content and extension are served
 * afterwards; deletion of the old S3 object is covered by S3ServiceTest::testUploadFileDeletesPreviousFileIfItExists().
 */
class ReplaceFileWithDifferentExtensionTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testReplacingFileWithDifferentExtensionServesOnlyTheNewFileAfterward(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'replace-file-with-different-extension',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $originalFile = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $originalUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $originalFile,
            self::TOKEN,
            ['Content-Disposition' => 'attachment; filename=original.jpg']
        );
        $this->assertIsCreatedResponse($originalUploadResponse, false);

        $originalDownloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($originalDownloadResponse, 'image/jpeg');
        $this->assertStringContainsString('.jpg', $originalDownloadResponse->getHeader('Content-Disposition')[0]);

        $replacementFilePath = __DIR__.'/../../Asset/replace-file-with-different-extension.bin';
        $this->generateDeterministicFile(24681357, 2048, $replacementFilePath);
        $replacementContent = \Safe\file_get_contents($replacementFilePath);
        $replacementFile = \Safe\fopen($replacementFilePath, 'r');

        $replaceResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $replacementFile,
            self::TOKEN,
            ['Content-Disposition' => 'attachment; filename=replacement.txt']
        );
        unlink($replacementFilePath);
        $this->assertIsCreatedResponse($replaceResponse, false);

        $replacementDownloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($replacementDownloadResponse, 'text/plain');
        $this->assertStringContainsString('.txt', $replacementDownloadResponse->getHeader('Content-Disposition')[0]);
        $this->assertSame($replacementContent, (string) $replacementDownloadResponse->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
