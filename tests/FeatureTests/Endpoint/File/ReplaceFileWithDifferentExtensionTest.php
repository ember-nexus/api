<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * A file's storage key includes its extension (see FileService::getStorageBucketKey()), so replacing a file with
 * one of a *different* extension writes to a different key than the original - the original has to be explicitly
 * deleted (see UploadFileOperationFactory/S3Service), or it would be left as a permanent orphan, unlike a
 * same-extension replace which naturally overwrites in place. This proves the client-observable side end-to-end:
 * after such a replace, only the new content/type is ever served, under the new extension. Whether the old S3
 * object is actually gone (rather than merely unreferenced) isn't observable through the public API at all - that
 * part is covered at the unit level instead (see S3ServiceTest::testUploadFileDeletesPreviousFileIfItExists()).
 */
#[Group('test')]
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
