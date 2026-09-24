<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors FileTopLevelPropertyTest, but targets relations, fetched directly and inside the /related, /children
 * and /parents collections.
 */
class FileTopLevelPropertyOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * @return array{0: string, 1: string, 2: string} [startNodeId, endNodeId, relationId]
     */
    private function createRelationWithFile(string $name): array
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

        $relationResponse = $this->runPostRequest(
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
        $relationId = $this->getUuidFromLocation($relationResponse);

        $filePath = __DIR__.'/../../Asset/'.$name.'.bin';
        $this->generateDeterministicFile(45362718, 128, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $fileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($fileResponse, false);
        unlink($filePath);

        return [$startNodeId, $endNodeId, $relationId];
    }

    private function assertFileShape(array $file): void
    {
        $this->assertArrayHasKey('contentLength', $file);
        $this->assertSame(128, $file['contentLength']);
        $this->assertArrayHasKey('extension', $file);
        $this->assertArrayHasKey('mimeType', $file);
        $this->assertArrayHasKey('hash', $file);
        $this->assertArrayHasKey('sha256', $file['hash']);
        $this->assertSame(64, strlen($file['hash']['sha256']));
    }

    public function testFileIsTopLevelAndHasFileIsInDataOnRelation(): void
    {
        [$startNodeId, $endNodeId, $relationId] = $this->createRelationWithFile('top-level-file-property-relation');

        $response = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('file', $body);
        $this->assertArrayNotHasKey('file', $body['data']);
        $this->assertFileShape($body['file']);
        $this->assertTrue($body['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $startNodeId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $endNodeId), self::TOKEN);
    }

    public function testRelatedCollectionIncludesFileAndHasFileForRelation(): void
    {
        [$startNodeId, $endNodeId, $relationId] = $this->createRelationWithFile('related-includes-file-relation');

        $relatedResponse = $this->runGetRequest(sprintf('/%s/related', $startNodeId), self::TOKEN);
        $this->assertIsCollectionResponse($relatedResponse);
        $relatedBody = \Safe\json_decode((string) $relatedResponse->getBody(), true);

        $found = null;
        foreach ($relatedBody['relations'] as $relation) {
            if ($relation['id'] === $relationId) {
                $found = $relation;
                break;
            }
        }
        $this->assertNotNull($found, 'Relation not found in /related collection response.');
        $this->assertArrayHasKey('file', $found);
        $this->assertFileShape($found['file']);
        $this->assertTrue($found['data']['hasFile']);
        $this->assertArrayNotHasKey('file', $found['data']);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $startNodeId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $endNodeId), self::TOKEN);
    }

    public function testChildrenCollectionIncludesFileAndHasFileForOwnsRelation(): void
    {
        $parentResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'children-includes-file-parent',
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
                    'name' => 'children-includes-file-child',
                ],
            ]
        );
        $childId = $this->getUuidFromLocation($childResponse);

        // find the implicit OWNS relation created between parent and child
        $relatedResponse = $this->runGetRequest(sprintf('/%s/related', $childId), self::TOKEN);
        $relatedBody = \Safe\json_decode((string) $relatedResponse->getBody(), true);
        $ownsRelationId = null;
        foreach ($relatedBody['relations'] as $relation) {
            if ('OWNS' === $relation['type'] && $relation['start'] === $parentId) {
                $ownsRelationId = $relation['id'];
                break;
            }
        }
        $this->assertNotNull($ownsRelationId, 'Could not find implicit OWNS relation between parent and child.');

        $filePath = __DIR__.'/../../Asset/children-includes-file-relation.bin';
        $this->generateDeterministicFile(87162534, 128, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $fileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $ownsRelationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($fileResponse, false);
        unlink($filePath);

        $childrenResponse = $this->runGetRequest(sprintf('/%s/children', $parentId), self::TOKEN);
        $this->assertIsCollectionResponse($childrenResponse);
        $childrenBody = \Safe\json_decode((string) $childrenResponse->getBody(), true);

        $found = null;
        foreach ($childrenBody['relations'] as $relation) {
            if ($relation['id'] === $ownsRelationId) {
                $found = $relation;
                break;
            }
        }
        $this->assertNotNull($found, 'OWNS relation not found in /children collection response.');
        $this->assertArrayHasKey('file', $found);
        $this->assertFileShape($found['file']);
        $this->assertTrue($found['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $childId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $parentId), self::TOKEN);
    }

    public function testParentsCollectionIncludesFileAndHasFileForOwnsRelation(): void
    {
        $parentResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'parents-includes-file-parent',
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
                    'name' => 'parents-includes-file-child',
                ],
            ]
        );
        $childId = $this->getUuidFromLocation($childResponse);

        $relatedResponse = $this->runGetRequest(sprintf('/%s/related', $childId), self::TOKEN);
        $relatedBody = \Safe\json_decode((string) $relatedResponse->getBody(), true);
        $ownsRelationId = null;
        foreach ($relatedBody['relations'] as $relation) {
            if ('OWNS' === $relation['type'] && $relation['start'] === $parentId) {
                $ownsRelationId = $relation['id'];
                break;
            }
        }
        $this->assertNotNull($ownsRelationId, 'Could not find implicit OWNS relation between parent and child.');

        $filePath = __DIR__.'/../../Asset/parents-includes-file-relation.bin';
        $this->generateDeterministicFile(19283765, 128, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $fileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $ownsRelationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($fileResponse, false);
        unlink($filePath);

        $parentsResponse = $this->runGetRequest(sprintf('/%s/parents', $childId), self::TOKEN);
        $this->assertIsCollectionResponse($parentsResponse);
        $parentsBody = \Safe\json_decode((string) $parentsResponse->getBody(), true);

        $found = null;
        foreach ($parentsBody['relations'] as $relation) {
            if ($relation['id'] === $ownsRelationId) {
                $found = $relation;
                break;
            }
        }
        $this->assertNotNull($found, 'OWNS relation not found in /parents collection response.');
        $this->assertArrayHasKey('file', $found);
        $this->assertFileShape($found['file']);
        $this->assertTrue($found['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $childId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $parentId), self::TOKEN);
    }

    public function testHasFileIsFalseAfterFileDeletionOnRelation(): void
    {
        [$startNodeId, $endNodeId, $relationId] = $this->createRelationWithFile('has-file-false-after-delete-relation');

        $this->runDeleteRequest(sprintf('/%s/file', $relationId), self::TOKEN);

        $response = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayNotHasKey('file', $body);
        $this->assertFalse($body['data']['hasFile']);

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $startNodeId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $endNodeId), self::TOKEN);
    }

    public function testPutFullReplaceDoesNotWipeFileOnRelation(): void
    {
        [$startNodeId, $endNodeId, $relationId] = $this->createRelationWithFile('put-preserves-file-relation');

        $putResponse = $this->runPutRequest(
            sprintf('/%s', $relationId),
            self::TOKEN,
            ['name' => 'replaced-via-put-relation']
        );
        $this->assertNoContentResponse($putResponse);

        $getResponse = $this->runGetRequest(sprintf('/%s', $relationId), self::TOKEN);
        $body = \Safe\json_decode((string) $getResponse->getBody(), true);

        $this->assertArrayHasKey('file', $body);
        $this->assertTrue($body['data']['hasFile']);
        $this->assertSame('replaced-via-put-relation', $body['data']['name']);

        $fileResponse = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($fileResponse, 'text/plain');

        $this->runDeleteRequest(sprintf('/%s', $relationId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $startNodeId), self::TOKEN);
        $this->runDeleteRequest(sprintf('/%s', $endNodeId), self::TOKEN);
    }
}
