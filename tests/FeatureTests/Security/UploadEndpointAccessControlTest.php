<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Verifies authorization on the `/upload/{id}` endpoints (PATCH, HEAD, DELETE):
 * - all three require the requesting user to be the upload's owner (`Upload::getUploadOwner()`); a different user
 *   is rejected with 404, exactly as if the upload did not exist.
 * - PATCH and HEAD additionally re-check UPDATE access on the upload's target element on every call, so an
 *   in-progress upload whose target became inaccessible to its own owner can no longer be continued or even
 *   inspected - DELETE performs no such re-check, so the owner can still cancel it.
 *
 * Reuses the "security.filePermission" scenario's two unrelated users (User A owns everything it creates, User B
 * has no relation to anything User A owns) for the ownership checks.
 */
#[Group('test')]
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

        // none of the denied attempts had any effect: the real owner still finds the upload exactly as it left it
        $headResponseAfter = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertSame(204, $headResponseAfter->getStatusCode());
        $this->assertSame('?0', $headResponseAfter->getHeader('Upload-Complete')[0]);
        $this->assertSame('0', $headResponseAfter->getHeader('Upload-Offset')[0]);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN_OWNER);
    }

    /**
     * PatchUploadController and HeadUploadController both re-check UPDATE access on the upload's target element
     * on every call (see the comment in HeadUploadController::headUpload()): if the owner's access to the target
     * is revoked while an upload is in progress, the upload can no longer be inspected or continued, even by its
     * own owner. DeleteUploadController performs no such re-check, so cancelling the now-stuck upload still works.
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

        // DELETE only checks upload ownership, not target access, so the now permanently stuck upload can still
        // be cancelled by its owner - the target element itself remains inaccessible/orphaned.
        $deleteResponse = $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN_OWNER);
        $this->assertIsDeletedResponse($deleteResponse);
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
