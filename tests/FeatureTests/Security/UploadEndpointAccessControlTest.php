<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that PATCH, HEAD and DELETE on `/upload/{id}` are restricted to the upload's owner (404 otherwise),
 * using the two unrelated users of the "security.filePermission" scenario.
 */
class UploadEndpointAccessControlTest extends BaseRequestTestCase
{
    private const string TOKEN_OWNER = 'secret-token:V8m72O3ovtRU09JrdbJRnh';
    private const string TOKEN_STRANGER = 'secret-token:3JMIdZMTqEF27mZHDO9AoA';

    public function testUploadOwnerCanCompleteUploadLifecycle(): void
    {
        [$elementId, $uploadId] = $this->createElementWithInProgressUpload(self::TOKEN_OWNER);

        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertSame(204, $headResponse->getStatusCode());

        $finishResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN_OWNER,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertNoContentResponse($finishResponse);

        $getFileResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN_OWNER);
        $this->assertSame(200, $getFileResponse->getStatusCode());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    public function testDifferentUserCannotPatchHeadOrDeleteUpload(): void
    {
        [$elementId, $uploadId] = $this->createElementWithInProgressUpload(self::TOKEN_OWNER);

        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_STRANGER);
        $this->assertSame(404, $headResponse->getStatusCode());

        $patchResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN_STRANGER,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponse, 404);

        $deleteResponse = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_STRANGER);
        $this->assertIsProblemResponse($deleteResponse, 404);

        // the denied attempts must not have changed the upload
        $headResponseAfter = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertSame(204, $headResponseAfter->getStatusCode());
        $this->assertSame('?0', $headResponseAfter->getHeader('Upload-Complete')[0]);
        $this->assertSame('0', $headResponseAfter->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    /**
     * PATCH and HEAD re-check UPDATE access on the upload's target on every call; once the owner loses access to the
     * target, the upload is cancelled (see CancelUploadOnAccessLossTest), so there is nothing left to delete either.
     */
    public function testUploadCanNoLongerBeInspectedOrCompletedOnceOwnerLosesAccessToTarget(): void
    {
        [$elementId, $uploadId, $ownsRelationId] = $this->createElementWithInProgressUpload(self::TOKEN_OWNER, true);

        // revoke the owner's own access to the target element by removing the only relation which grants it
        $revokeResponse = $this->runDeleteRequest(sprintf('/%s', $ownsRelationId), self::TOKEN_OWNER);
        $this->assertIsDeletedResponse($revokeResponse);
        $elementResponseAfterRevoke = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
        $this->assertIsProblemResponse($elementResponseAfterRevoke, 404);

        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertSame(404, $headResponse->getStatusCode());

        $patchResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN_OWNER,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponse, 404);

        // the upload was cancelled when the access was revoked
        $deleteResponse = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertIsProblemResponse($deleteResponse, 404);
    }

    /**
     * @return array{0: string, 1: string, 2: string} [elementId, uploadId, ownsRelationId]
     */
    private function createElementWithInProgressUpload(string $token, bool $includeOwnsRelationId = false): array
    {
        $elementResponse = $this->runPostRequest(
            '/',
            $token,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'upload-access-control-test',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            $token,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $ownsRelationId = '';
        if ($includeOwnsRelationId) {
            $parentsResponse = $this->runGetRequest(sprintf('/%s/parents', $elementId), $token);
            $body = $this->getBody($parentsResponse);
            $ownsRelationId = $body['relations'][0]['id'];
        }

        return [$elementId, $uploadId, $ownsRelationId];
    }
}
