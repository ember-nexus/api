<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Covers:
 * - 'file' is exposed as a top-level property (sibling of id/type/data), 'hasFile' is a plain boolean inside
 *   'data', kept in sync automatically.
 * - 'file' is present, and 'hasFile' is omitted from top-level collection/parent listings ('hasFile' remains
 *   visible in 'data', 'file' is not).
 * - both 'file' and 'hasFile' are reserved: a client can not set them directly via POST/PUT/PATCH.
 * - a PUT (full data replace) does not wipe an element's file.
 */
#[Group('test')]
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

    public function testCollectionListingOmitsFileButKeepsHasFile(): void
    {
        // a dedicated, freshly created parent is used (instead of e.g. the shared botanicExample user's
        // children) so the collection queried here is not shared with, and can not race against, any other
        // (parallel) test.
        $parentResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'collection-omits-file-parent',
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
                    'name' => 'collection-omits-file-child',
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
        $this->assertArrayNotHasKey('file', $foundElement);
        $this->assertTrue($foundElement['data']['hasFile']);

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
