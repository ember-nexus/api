<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * `Upload.uploadTarget` (see UploadService) is a plain property, not a graph edge, so deleting an element does
 * not automatically take any in-progress resumable upload(s) still targeting it along with it - without explicit
 * cleanup, they would linger, still HEAD-able, until the `cron:delete-expired-uploads` grace period eventually
 * catches up with them. `DeleteElementController` cleans these up immediately instead (see
 * UploadService::deleteUploadsTargeting()); this covers that end-to-end, for both an upload with no chunks
 * uploaded yet and one with an already-uploaded chunk.
 */
#[Group('test')]
class DeletingElementCleansUpInProgressUploadTest extends BaseRequestTestCase
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

    public function testDeletingElementRemovesUploadWithNoChunksUploadedYet(): void
    {
        $elementId = $this->createElement('delete-element-cleans-up-upload-no-chunks');

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

        $headResponseBeforeDelete = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponseBeforeDelete->getStatusCode());

        $deleteElementResponse = $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteElementResponse);

        $headResponseAfterDelete = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(404, $headResponseAfterDelete->getStatusCode());
    }

    public function testDeletingElementRemovesUploadWithAnAlreadyUploadedChunk(): void
    {
        $elementId = $this->createElement('delete-element-cleans-up-upload-with-chunk');

        $filePath = __DIR__.'/../../Asset/delete-element-cleans-up-upload-with-chunk.bin';
        $this->generateDeterministicFile(65432198, 5 * 1024 * 1024, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        unlink($filePath);
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $deleteElementResponse = $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteElementResponse);

        $headResponseAfterDelete = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(404, $headResponseAfterDelete->getStatusCode());

        // the upload resource is really gone, not just inaccessible - a further attempt to continue it must
        // report the same "not found" a client would get for any other unknown upload id, not a crash
        $patchResponse = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 5 * 1024 * 1024,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($patchResponse, 404);
    }
}
