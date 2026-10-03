<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors a subset of FileDigestTest, but targets a relation instead of a node.
 */
class FileDigestOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createRelation(string $name): string
    {
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name.'-start',
                ],
            ]
        );
        $startNodeId = $this->getUuidFromLocation($startNode);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name.'-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => $name,
                ],
            ]
        );

        return $this->getUuidFromLocation($relation);
    }

    public function testDownloadResponseIncludesReprDigestHeaderOnRelation(): void
    {
        $relationId = $this->createRelation('digest-response-headers-relation');

        $filePath = __DIR__.'/../../Asset/file-digest-response-relation.bin';
        $this->generateDeterministicFile(11223355, 4096, $filePath);
        $expectedHash = hash_file('sha256', $filePath);
        $expectedHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($expectedHash)));

        $file = \Safe\fopen($filePath, 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);
        unlink($filePath);

        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($downloadResponse, 'text/plain');
        $this->assertSame([$expectedHeaderValue], $downloadResponse->getHeader('Repr-Digest'));
        $this->assertSame([], $downloadResponse->getHeader('Content-Digest'));

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
    }

    public function testRangedResponseIncludesReprDigestButNotContentDigestOnRelation(): void
    {
        $relationId = $this->createRelation('digest-ranged-response-relation');

        $filePath = __DIR__.'/../../Asset/file-digest-ranged-relation.bin';
        $this->generateDeterministicFile(55667799, 4096, $filePath);
        $expectedHash = hash_file('sha256', $filePath);
        $expectedHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($expectedHash)));

        $file = \Safe\fopen($filePath, 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);
        unlink($filePath);

        $downloadResponse = $this->runGetRequest(
            sprintf('/%s/file', $relationId),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );
        $this->assertSame(206, $downloadResponse->getStatusCode());
        $this->assertSame([$expectedHeaderValue], $downloadResponse->getHeader('Repr-Digest'));
        $this->assertSame([], $downloadResponse->getHeader('Content-Digest'));

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
    }

    public function testUploadWithCorrectDigestSucceedsOnRelation(): void
    {
        $relationId = $this->createRelation('digest-upload-correct-relation');

        $filePath = __DIR__.'/../../Asset/file-digest-correct-relation.bin';
        $this->generateDeterministicFile(99887799, 2048, $filePath);
        $hash = hash_file('sha256', $filePath);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($hash)));

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsCreatedResponse($response, false);
        unlink($filePath);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
    }

    public function testUploadWithMismatchedDigestIsRejectedOnRelation(): void
    {
        $relationId = $this->createRelation('digest-upload-mismatch-relation');

        $filePath = __DIR__.'/../../Asset/file-digest-mismatch-relation.bin';
        $this->generateDeterministicFile(13245769, 2048, $filePath);

        $bogusHash = str_repeat('00', 32);
        $digestHeaderValue = sprintf('sha-256=:%s:', base64_encode(hex2bin($bogusHash)));

        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
                'Repr-Digest' => $digestHeaderValue,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
        unlink($filePath);

        // the relation must not end up with a file property, since the upload was rejected
        $getResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsProblemResponse($getResponse, 404);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
    }
}
