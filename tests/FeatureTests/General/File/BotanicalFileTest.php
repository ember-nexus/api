<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Exercises file download, replace and delete behaviour against images seeded by the botanical reference
 * dataset scenario ("general.botanicExample"). Nodes touched by mutating tests (replace/delete) are picked
 * from the plants which are not referenced by any other test or documentation example, so this test can run
 * safely alongside the rest of the suite.
 */
#[Group('test')]
class BotanicalFileTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private const string ROSE_ID = '0fdd52ba-55da-430c-b015-3277a231e895';
    private const string ROSE_SHA256 = '7124bba0a86dcc10e1b5c78ef968b92e1fe681c16d0f2ec9f35b0e8448c1a4c0';
    private const int ROSE_CONTENT_LENGTH = 138937;

    private const string TULIP_ID = '8940d70b-5b6f-43b7-bee4-41d073396ff8';
    private const string TULIP_SHA256 = 'c4c242897c43a74e9e4a8b5bf89e6b2cb1cbd99f8d621f33eea175cbf6dfab97';
    private const int TULIP_CONTENT_LENGTH = 105488;

    private const string ORCHID_ID = 'ba6fd9dd-dfec-488b-864c-b7dca1602fd9';
    private const string DAISY_ID = 'dde20559-c290-4d42-a6f3-87d5d326b66f';
    private const string AZALEA_ID = 'bfdac7bb-5a6d-497e-97ea-2dcac44bbdf8';
    private const string CHERRY_BLOSSOM_ID = 'd1a8ef01-2947-4e19-a381-5af0d0d7abca';

    public function testDownloadedRoseImageHasCorrectHashAndLength(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');

        // the element's 'name' property ("Rose") is used as the download's filename, with the stored file
        // extension ("jpg") appended; see ElementService::getFileName().
        $this->assertStringContainsString('filename=Rose.jpg', $response->getHeader('Content-Disposition')[0]);

        $body = (string) $response->getBody();
        $this->assertSame(self::ROSE_CONTENT_LENGTH, strlen($body));
        $this->assertSame(self::ROSE_SHA256, hash('sha256', $body));
    }

    public function testDownloadedTulipImageHasCorrectHashAndLength(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::TULIP_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');

        $body = (string) $response->getBody();
        $this->assertSame(self::TULIP_CONTENT_LENGTH, strlen($body));
        $this->assertSame(self::TULIP_SHA256, hash('sha256', $body));
    }

    public function testReplaceOrchidImageWithNewFile(): void
    {
        $newFile = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $replaceResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', self::ORCHID_ID),
            $newFile,
            self::TOKEN,
            [
                'Content-Disposition' => 'inline; filename=cherry-blossoms.jpg',
                'Content-Type' => 'image/jpg',
            ]
        );
        $this->assertIsCreatedResponse($replaceResponse, false);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', self::ORCHID_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'image/jpeg');
        $this->assertSame(63933, strlen((string) $downloadResponse->getBody()));
    }

    public function testDeleteDaisyImage(): void
    {
        $deleteResponse = $this->runDeleteRequest(sprintf('/%s/file', self::DAISY_ID), self::TOKEN);
        $this->assertIsDeletedResponse($deleteResponse);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', self::DAISY_ID), self::TOKEN);
        $this->assertIsProblemResponse($downloadResponse, 404);
    }

    public function testReplaceAzaleaImageWithResumableUpload(): void
    {
        $fileSeed = 91827364;
        $fileSize = 6 * 1024 * 1024;
        $chunkSize = 5 * 1024 * 1024; // first chunk must be at least 5MB, see uploadMinChunkSizeInBytes
        $filePath = __DIR__.'/../../Asset/azalea-replacement.bin';

        $this->generateDeterministicFile($fileSeed, $fileSize, $filePath);
        $chunks = $this->splitFileToChunks($filePath, $chunkSize);

        $firstChunk = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', self::AZALEA_ID),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Disposition' => 'inline; filename=azalea-replacement.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $secondChunk = \Safe\fopen($chunks[1], 'r');
        $finishUploadResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $secondChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => $chunkSize,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($finishUploadResponse);
        $this->assertSame('?1', $finishUploadResponse->getHeader('Upload-Complete')[0]);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', self::AZALEA_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertSame($fileSize, strlen((string) $downloadResponse->getBody()));

        $this->cleanupChunks($chunks);
        unlink($filePath);
    }

    public function testReplaceCherryBlossomImageWithBogusGeneratedFile(): void
    {
        $fileSeed = 55667788;
        $fileSize = 32 * 1024;
        $filePath = __DIR__.'/../../Asset/cherry-blossom-bogus.bin';

        $this->generateDeterministicFile($fileSeed, $fileSize, $filePath);
        $expectedHash = hash_file('sha256', $filePath);

        $bogusFile = \Safe\fopen($filePath, 'r');
        $replaceResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', self::CHERRY_BLOSSOM_ID),
            $bogusFile,
            self::TOKEN,
            [
                'Content-Disposition' => 'inline; filename=bogus.bin',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($replaceResponse, false);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', self::CHERRY_BLOSSOM_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $downloadedBody = (string) $downloadResponse->getBody();
        $this->assertSame($fileSize, strlen($downloadedBody));
        $this->assertSame($expectedHash, hash('sha256', $downloadedBody));

        unlink($filePath);
    }
}
