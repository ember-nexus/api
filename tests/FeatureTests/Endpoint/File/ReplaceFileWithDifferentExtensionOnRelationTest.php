<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Mirrors ReplaceFileWithDifferentExtensionTest, but targets a relation instead of a node.
 */
#[Group('test')]
class ReplaceFileWithDifferentExtensionOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testReplacingFileWithDifferentExtensionServesOnlyTheNewFileAfterwardOnRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'replace-file-with-different-extension-relation');

        $originalFile = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $originalUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $originalFile,
            self::TOKEN,
            ['Content-Disposition' => 'attachment; filename=original.jpg']
        );
        $this->assertIsCreatedResponse($originalUploadResponse, false);

        $originalDownloadResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($originalDownloadResponse, 'image/jpeg');
        $this->assertStringContainsString('.jpg', $originalDownloadResponse->getHeader('Content-Disposition')[0]);

        $replacementFilePath = __DIR__.'/../../Asset/replace-file-with-different-extension-relation.bin';
        $this->generateDeterministicFile(13572468, 2048, $replacementFilePath);
        $replacementContent = \Safe\file_get_contents($replacementFilePath);
        $replacementFile = \Safe\fopen($replacementFilePath, 'r');

        $replaceResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $relationId),
            $replacementFile,
            self::TOKEN,
            ['Content-Disposition' => 'attachment; filename=replacement.txt']
        );
        unlink($replacementFilePath);
        $this->assertIsCreatedResponse($replaceResponse, false);

        $replacementDownloadResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($replacementDownloadResponse, 'text/plain');
        $this->assertStringContainsString('.txt', $replacementDownloadResponse->getHeader('Content-Disposition')[0]);
        $this->assertSame($replacementContent, (string) $replacementDownloadResponse->getBody());

        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN));
        $this->assertSame('txt', $body['file']['extension']);
        $this->assertSame(hash('sha256', $replacementContent), $body['file']['hash']['sha256']);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }
}
