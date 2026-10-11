<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * A failure while merging the chunks and updating the element in `UploadFinalizationService::finalize()` (e.g. S3
 * or the database not being reachable) is not the client's fault: the upload is put back to unfinalized (not
 * deleted) with its hash state preserved, so the client retries completion with an empty `PATCH`,
 * `Upload-Complete: ?1`, at the offset reported by `HEAD`.
 *
 * There is no existing, reliable way in this suite to make the S3 merge itself fail without disturbing concurrent
 * tests (the storage bucket is shared and can not be emptied or renamed while other tests are running, and MinIO
 * here has no reachable per-request policy control). Instead, this forces the failure on the database side of the
 * same code path: a uniqueness constraint on a label used by no other element makes the merge's own write of
 * `hasFile = true` onto the upload's target collide with a decoy node created just for this test, so the S3 merge
 * of the chunks genuinely succeeds and only the following graph write fails. `finalize()` treats that exactly the
 * same as a failing S3 merge. Runs in the `command` group because it manipulates a global schema constraint (the
 * constraint itself is scoped to a label no other test uses, so it does not affect concurrent parallel tests).
 */
#[Group('command')]
class ResumableUploadUnfinalizedStateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    // the element's own type becomes its graph label, so the constraint's label must be used by no other element;
    // the decoy carries an additional, second label so cleanup can remove it without also deleting the upload's
    // real target, which carries the first label too
    private const string LABEL = 'ResumableUploadUnfinalizedStateTestTarget';
    private const string DECOY_LABEL = 'ResumableUploadUnfinalizedStateTestDecoy';
    private const string CONSTRAINT_NAME = 'resumable_upload_unfinalized_state_test_has_file_unique';

    // the decoy node claims the only allowed `true` value of the constrained property first, so it is the
    // target's own later write of `hasFile = true` which introduces the duplicate and fails
    private function forceNextFinalizationToFailInTheDatabase(): void
    {
        $client = $this->getCypherClient();
        $client->run(sprintf('CREATE CONSTRAINT %s IF NOT EXISTS FOR (n:%s) REQUIRE n.hasFile IS UNIQUE', self::CONSTRAINT_NAME, self::LABEL));
        $client->run(sprintf('CREATE (:%s:%s {hasFile: true})', self::LABEL, self::DECOY_LABEL));
    }

    private function removeDatabaseFailureSimulation(): void
    {
        $client = $this->getCypherClient();
        $client->run(sprintf('MATCH (n:%s) DETACH DELETE n', self::DECOY_LABEL));
        $client->run(sprintf('DROP CONSTRAINT %s IF EXISTS', self::CONSTRAINT_NAME));
    }

    public function testFailedFinalizationLeavesUploadUnfinalizedAndRetrySucceeds(): void
    {
        $this->forceNextFinalizationToFailInTheDatabase();

        try {
            $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
                'type' => self::LABEL,
                'data' => ['name' => 'resumable-upload-unfinalized-state'],
            ]));

            $createUploadResponse = $this->runUploadRequest(
                'POST',
                sprintf('/%s/file', $elementId),
                '',
                self::TOKEN,
                [
                    'Upload-Complete' => '?0',
                    'Content-Type' => 'application/octet-stream',
                ]
            );
            $this->assertNoContentResponse($createUploadResponse, true);
            $uploadId = $this->getUuidFromLocation($createUploadResponse);

            $content = str_repeat('unfinalized-upload-content-', 64);

            $failingFinishResponse = $this->runUploadRequest(
                'PATCH',
                sprintf('/upload/%s', $uploadId),
                $content,
                self::TOKEN,
                [
                    'Upload-Complete' => '?1',
                    'Upload-Offset' => 0,
                    'Content-Type' => 'application/partial-upload',
                ]
            );
            $this->assertIsProblemResponse($failingFinishResponse, 500);

            // the upload is still there, resumable, not deleted, and reports the offset of the data already received
            $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
            $this->assertSame(204, $headResponse->getStatusCode());
            $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
            $this->assertSame((string) strlen($content), $headResponse->getHeader('Upload-Offset')[0]);

            // the failed graph write never committed, so the element still has no file
            $elementAfterFailure = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN));
            $this->assertArrayNotHasKey('file', $elementAfterFailure);
            $this->assertFalse($elementAfterFailure['data']['hasFile'] ?? false);

            $this->removeDatabaseFailureSimulation();

            // the client retries completion with an empty PATCH at the offset reported by HEAD; the preserved hash
            // state is what allows the retry to produce the correct hash of the whole file without re-sending it
            $retryResponse = $this->runUploadRequest(
                'PATCH',
                sprintf('/upload/%s', $uploadId),
                '',
                self::TOKEN,
                [
                    'Upload-Complete' => '?1',
                    'Upload-Offset' => strlen($content),
                    'Content-Type' => 'application/partial-upload',
                ]
            );
            $this->assertNoContentResponse($retryResponse);
            $this->assertSame('?1', $retryResponse->getHeader('Upload-Complete')[0]);
            $this->assertSame((string) strlen($content), $retryResponse->getHeader('Upload-Offset')[0]);

            // the upload resource is gone once finished
            $headAfterCompletion = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
            $this->assertSame(404, $headAfterCompletion->getStatusCode());

            $elementAfterRetry = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN));
            $this->assertTrue($elementAfterRetry['data']['hasFile']);
            $this->assertSame(strlen($content), $elementAfterRetry['file']['contentLength']);
            $this->assertSame(hash('sha256', $content), $elementAfterRetry['file']['hash']['sha256']);

            $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
            $this->assertSame($content, (string) $downloadResponse->getBody());

            $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        } finally {
            $this->removeDatabaseFailureSimulation();
        }
    }
}
