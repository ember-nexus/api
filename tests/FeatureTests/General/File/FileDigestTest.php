<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers RFC 9530 ("Digest Fields") support: `Repr-Digest`/`Content-Digest` response headers on file downloads,
 * and client-supplied `Repr-Digest`/`Content-Digest` request headers being verified against the uploaded file.
 */
#[Group('test')]
class FileDigestTest extends BaseRequestTestCase
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

    public function testDownloadResponseIncludesReprDigestHeader(): void
    {
        $elementId = $this->createElement('digest-response-headers');

        $filePath = __DIR__.'/../../Asset/file-digest-response.bin';
        $this->generateDeterministicFile(11223344, 4096, $filePath);
        $expectedHash = hash_file('sha256', $filePath);
        $expectedHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($expectedHash)));

        $file = \Safe\fopen($filePath, 'r');
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
        $this->assertSame([$expectedHeaderValue], $downloadResponse->getHeader('Repr-Digest'));
        // Content-Digest is deliberately not set at all: for a full response it would always equal Repr-Digest
        // (no extra information), and it is never set on a partial response either (see below), so it never
        // carries any information Repr-Digest doesn't already provide.
        $this->assertSame([], $downloadResponse->getHeader('Content-Digest'));

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testRangedResponseIncludesReprDigestButNotContentDigest(): void
    {
        $elementId = $this->createElement('digest-ranged-response');

        $filePath = __DIR__.'/../../Asset/file-digest-ranged.bin';
        $this->generateDeterministicFile(55667788, 4096, $filePath);
        $expectedHash = hash_file('sha256', $filePath);
        $expectedHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($expectedHash)));

        $file = \Safe\fopen($filePath, 'r');
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

        $downloadResponse = $this->runGetRequest(
            sprintf('/%s/file', $elementId),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );
        $this->assertSame(206, $downloadResponse->getStatusCode());
        $this->assertSame([$expectedHeaderValue], $downloadResponse->getHeader('Repr-Digest'));
        $this->assertSame([], $downloadResponse->getHeader('Content-Digest'));

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testUploadWithCorrectDigestSucceeds(): void
    {
        $elementId = $this->createElement('digest-upload-correct');

        $filePath = __DIR__.'/../../Asset/file-digest-correct.bin';
        $this->generateDeterministicFile(99887766, 2048, $filePath);
        $hash = hash_file('sha256', $filePath);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($hash)));

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsCreatedResponse($response, false);
        unlink($filePath);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testUploadWithMismatchedDigestIsRejected(): void
    {
        $elementId = $this->createElement('digest-upload-mismatch');

        $filePath = __DIR__.'/../../Asset/file-digest-mismatch.bin';
        $this->generateDeterministicFile(13245768, 2048, $filePath);

        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        unlink($filePath);

        // the element must not end up with a file property, since the upload was rejected
        $getResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsProblemResponse($getResponse, 404);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testUploadWithUnsupportedDigestAlgorithmIsRejected(): void
    {
        $elementId = $this->createElement('digest-upload-unsupported');

        $filePath = __DIR__.'/../../Asset/file-digest-unsupported.bin';
        $this->generateDeterministicFile(24681012, 2048, $filePath);

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => 'md5=:1B2M2Y8AsgTpgAmY7PhCfg==:',
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        unlink($filePath);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testResumableUploadCompletionVerifiesDigest(): void
    {
        $elementId = $this->createElement('digest-resumable');

        $fileSeed = 987654321;
        $fileSize = 6 * 1024 * 1024;
        $chunkSize = 5 * 1024 * 1024;
        $filePath = __DIR__.'/../../Asset/file-digest-resumable.bin';

        $this->generateDeterministicFile($fileSeed, $fileSize, $filePath);
        $hash = hash_file('sha256', $filePath);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($hash)));
        $chunks = $this->splitFileToChunks($filePath, $chunkSize);

        $firstChunk = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
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
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertNoContentResponse($finishUploadResponse);

        $this->cleanupChunks($chunks);
        unlink($filePath);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testReplacingFileWithMismatchedDigestLeavesOriginalFileIntact(): void
    {
        $elementId = $this->createElement('digest-replace-mismatch');

        $originalFilePath = __DIR__.'/../../Asset/file-digest-replace-original.bin';
        $this->generateDeterministicFile(11223344, 2048, $originalFilePath);
        $originalContent = \Safe\file_get_contents($originalFilePath);

        $originalFile = \Safe\fopen($originalFilePath, 'r');
        $originalUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $originalFile,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($originalUploadResponse, false);
        unlink($originalFilePath);

        $replacementFilePath = __DIR__.'/../../Asset/file-digest-replace-replacement.bin';
        $this->generateDeterministicFile(55667788, 2048, $replacementFilePath);
        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $replacementFile = \Safe\fopen($replacementFilePath, 'r');
        $replacementResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $replacementFile,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($replacementResponse, 400);
        unlink($replacementFilePath);

        // the rejected replacement must not have overwritten the original file
        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $downloadResponse->getStatusCode());
        $this->assertSame($originalContent, (string) $downloadResponse->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testResumableReplaceWithMismatchedDigestLeavesOriginalFileIntact(): void
    {
        $elementId = $this->createElement('digest-resumable-replace-mismatch');

        $originalFilePath = __DIR__.'/../../Asset/file-digest-resumable-replace-original.bin';
        $this->generateDeterministicFile(99001122, 2048, $originalFilePath);
        $originalContent = \Safe\file_get_contents($originalFilePath);

        $originalFile = \Safe\fopen($originalFilePath, 'r');
        $originalUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $originalFile,
            self::TOKEN,
            ['Content-Type' => 'application/octet-stream']
        );
        $this->assertIsCreatedResponse($originalUploadResponse, false);
        unlink($originalFilePath);

        $replacementFileSeed = 33445566;
        $replacementFileSize = 6 * 1024 * 1024;
        $chunkSize = 5 * 1024 * 1024;
        $replacementFilePath = __DIR__.'/../../Asset/file-digest-resumable-replace-replacement.bin';

        $this->generateDeterministicFile($replacementFileSeed, $replacementFileSize, $replacementFilePath);
        $chunks = $this->splitFileToChunks($replacementFilePath, $chunkSize);
        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $firstChunk = \Safe\fopen($chunks[0], 'r');
        $createUploadResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $firstChunk,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
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
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($finishUploadResponse, 400);

        $this->cleanupChunks($chunks);
        unlink($replacementFilePath);

        // the rejected replacement must not have overwritten the original file
        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame(200, $downloadResponse->getStatusCode());
        $this->assertSame($originalContent, (string) $downloadResponse->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
