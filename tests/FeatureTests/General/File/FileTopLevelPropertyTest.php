<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that 'file' is a top-level property and 'hasFile' a boolean inside 'data', both for direct fetches and
 * collection responses; that clients can not write either property via POST/PATCH; and that PUT keeps the file.
 */
class FileTopLevelPropertyTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createElementWithFile(string $name): string
    {
        $postResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name,
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($postResponse);

        $filePath = __DIR__.'/../../Asset/file-top-level-property.bin';
        $this->generateDeterministicFile(19283746, 128, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $fileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($fileResponse, false);
        unlink($filePath);

        return $elementId;
    }

    public function testFileIsTopLevelAndHasFileIsInData(): void
    {
        $elementId = $this->createElementWithFile('top-level-file-property');

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('file', $body);
        $this->assertArrayNotHasKey('file', $body['data']);
        $this->assertArrayHasKey('contentLength', $body['file']);
        $this->assertTrue($body['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testCollectionListingIncludesFileAndHasFile(): void
    {
        // dedicated parent, so the collection can not race against other (parallel) tests
        $parentResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'collection-includes-file-parent',
                ],
            ]
        );
        $parentId = $this->getUuidFromLocation($parentResponse);

        $childResponse = $this->runPostRequest(
            sprintf('/%s', $parentId),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'collection-includes-file-child',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($childResponse);

        $filePath = __DIR__.'/../../Asset/file-top-level-property-collection.bin';
        $this->generateDeterministicFile(56473829, 128, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $fileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($fileResponse, false);
        unlink($filePath);

        $childrenResponse = $this->runGetRequest(sprintf('/%s/children', $parentId), self::TOKEN);
        $childrenBody = \Safe\json_decode((string) $childrenResponse->getBody(), true);

        $this->assertCount(1, $childrenBody['nodes']);
        $foundElement = $childrenBody['nodes'][0];
        $this->assertSame($elementId, $foundElement['id']);
        $this->assertTrue($foundElement['data']['hasFile']);
        $this->assertArrayHasKey('file', $foundElement);
        $this->assertSame(128, $foundElement['file']['contentLength']);
        $this->assertArrayHasKey('mimeType', $foundElement['file']);
        $this->assertArrayHasKey('extension', $foundElement['file']);
        $this->assertSame(64, strlen($foundElement['file']['hash']['sha256']));
        $this->assertArrayNotHasKey('file', $foundElement['data']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $parentId), self::TOKEN);
    }

    public function testHasFileIsFalseAfterFileDeletion(): void
    {
        $elementId = $this->createElementWithFile('has-file-false-after-delete');

        $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN);

        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayNotHasKey('file', $body);
        $this->assertFalse($body['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testDirectWriteToFilePropertyIsRejectedOnCreate(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'file' => ['contentLength' => 999],
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }

    public function testDirectWriteToHasFilePropertyIsRejectedOnCreate(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'hasFile' => true,
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }

    public function testDirectWriteToFilePropertyIsRejectedOnPatch(): void
    {
        $elementId = $this->createElementWithFile('patch-rejects-file');

        $response = $this->runPatchRequest(
            sprintf('/%s', $elementId),
            self::TOKEN,
            ['file' => ['contentLength' => 999]]
        );
        $this->assertIsProblemResponse($response, 400);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testDirectWriteToHasFilePropertyIsRejectedOnPatch(): void
    {
        $elementId = $this->createElementWithFile('patch-rejects-has-file');

        $response = $this->runPatchRequest(
            sprintf('/%s', $elementId),
            self::TOKEN,
            ['hasFile' => false]
        );
        $this->assertIsProblemResponse($response, 400);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testPutFullReplaceDoesNotWipeFile(): void
    {
        $elementId = $this->createElementWithFile('put-preserves-file');

        $putResponse = $this->runPutRequest(
            sprintf('/%s', $elementId),
            self::TOKEN,
            ['name' => 'replaced-via-put']
        );
        $this->assertNoContentResponse($putResponse);

        $getResponse = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = \Safe\json_decode((string) $getResponse->getBody(), true);

        $this->assertArrayHasKey('file', $body);
        $this->assertTrue($body['data']['hasFile']);
        $this->assertSame('replaced-via-put', $body['data']['name']);

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($fileResponse, 'text/plain');

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
