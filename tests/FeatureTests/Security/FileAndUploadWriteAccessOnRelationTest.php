<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Write access to the file and the uploads of a relation: users without access to the relation must not be able to
 * create, replace or delete its file or to interact with its uploads (404 in all cases). Counterpart of
 * FileEndpointAccessControlTest and UploadEndpointAccessControlTest, which cover nodes.
 *
 * Uses the two unrelated users of the "security.filePermission" scenario and creates a fresh relation per test.
 */
class FileAndUploadWriteAccessOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN_OWNER = 'secret-token:V8m72O3ovtRU09JrdbJRnh';
    private const string TOKEN_STRANGER = 'secret-token:3JMIdZMTqEF27mZHDO9AoA';

    public function testStrangerCannotWriteFileOfRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN_OWNER, 'relation-file-write-access');
        $fileUrl = sprintf('/%s/file', $relationId);

        // no file yet
        $this->assertIsProblemResponse($this->runUploadRequest('POST', $fileUrl, 'stranger', self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runUploadRequest('PUT', $fileUrl, 'stranger', self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runDeleteRequest($fileUrl, self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runGetRequest($fileUrl, self::TOKEN_OWNER), 404);

        // with a file
        $this->assertIsCreatedResponse($this->runUploadRequest('POST', $fileUrl, 'owner content', self::TOKEN_OWNER), false);

        $this->assertIsProblemResponse($this->runUploadRequest('POST', $fileUrl, 'stranger', self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runUploadRequest('PUT', $fileUrl, 'stranger', self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runDeleteRequest($fileUrl, self::TOKEN_STRANGER), 404);
        $this->assertIsProblemResponse($this->runGetRequest($fileUrl, self::TOKEN_STRANGER), 404);

        // nothing was changed
        $this->assertSame('owner content', (string) $this->runGetRequest($fileUrl, self::TOKEN_OWNER)->getBody());

        // the owner can still replace and delete it
        $this->assertIsCreatedResponse($this->runUploadRequest('PUT', $fileUrl, 'replaced', self::TOKEN_OWNER), false);
        $this->assertSame('replaced', (string) $this->runGetRequest($fileUrl, self::TOKEN_OWNER)->getBody());
        $this->assertIsDeletedResponse($this->runDeleteRequest($fileUrl, self::TOKEN_OWNER));

        $this->deleteEphemeralRelation(self::TOKEN_OWNER, $relationId);
    }

    public function testStrangerCannotCreateUploadForRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN_OWNER, 'relation-upload-creation-access');

        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            '',
            self::TOKEN_STRANGER,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsProblemResponse($response, 404);

        $this->deleteEphemeralRelation(self::TOKEN_OWNER, $relationId);
    }

    public function testStrangerCannotUseUploadOfRelation(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN_OWNER, 'relation-upload-access');

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            '',
            self::TOKEN_OWNER,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);
        $uploadUrl = sprintf('/upload/%s', $uploadId);

        $this->assertSame(404, $this->runHeadRequest($uploadUrl, self::TOKEN_STRANGER)->getStatusCode());
        $patchResponse = $this->runUploadRequest(
            'PATCH',
            $uploadUrl,
            'stranger',
            self::TOKEN_STRANGER,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponse, 404);
        $this->assertIsProblemResponse($this->runDeleteRequest($uploadUrl, self::TOKEN_STRANGER), 404);

        // the denied attempts changed nothing
        $headResponse = $this->runHeadRequest($uploadUrl, self::TOKEN_OWNER);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('0', $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN_OWNER), 404);

        // the owner can finish it
        $finishResponse = $this->runUploadRequest(
            'PATCH',
            $uploadUrl,
            'owner data',
            self::TOKEN_OWNER,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($finishResponse);
        $this->assertSame('owner data', (string) $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN_OWNER)->getBody());

        $this->deleteEphemeralRelation(self::TOKEN_OWNER, $relationId);
    }
}
