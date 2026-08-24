<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use DateTime;
use PHPUnit\Framework\Attributes\Group;

/**
 * Attaching, replacing or deleting a file on an element must count as a change to the element itself: its
 * 'updated' timestamp (and therefore its ETag) must move forward, even though 'updated' is not part of the
 * request body sent to the file endpoints at all.
 */
#[Group('test')]
class FileUploadUpdatesElementTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function getElementEtag(string $elementId): string
    {
        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $etagHeader = $response->getHeader('ETag');
        $this->assertCount(1, $etagHeader, 'Expected exactly one ETag header on the element response.');

        return $etagHeader[0];
    }

    private function getElementUpdatedTimestamp(string $elementId): string
    {
        $response = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        return $body['data']['updated'];
    }

    public function testAttachingAFileUpdatesTheElement(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-upload-updates-element',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $etagBeforeUpload = $this->getElementEtag($elementId);
        $updatedBeforeUpload = $this->getElementUpdatedTimestamp($elementId);

        // ensure the 'updated' timestamp (second precision) has a chance to actually move forward
        sleep(1);

        $filePath = __DIR__.'/../../Asset/file-upload-updates-element.bin';
        $this->generateDeterministicFile(11223344, 128, $filePath);
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

        $etagAfterUpload = $this->getElementEtag($elementId);
        $updatedAfterUpload = $this->getElementUpdatedTimestamp($elementId);

        $this->assertNotSame($etagBeforeUpload, $etagAfterUpload);
        $this->assertNotSame($updatedBeforeUpload, $updatedAfterUpload);
        $this->assertGreaterThan(
            new DateTime($updatedBeforeUpload),
            new DateTime($updatedAfterUpload)
        );

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testReplacingAFileUpdatesTheElement(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-replace-updates-element',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $firstFilePath = __DIR__.'/../../Asset/file-replace-updates-element-1.bin';
        $this->generateDeterministicFile(22334455, 128, $firstFilePath);
        $firstFile = \Safe\fopen($firstFilePath, 'r');
        $postFileResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $firstFile,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($postFileResponse, false);
        unlink($firstFilePath);

        $etagBeforeReplace = $this->getElementEtag($elementId);

        sleep(1);

        $secondFilePath = __DIR__.'/../../Asset/file-replace-updates-element-2.bin';
        $this->generateDeterministicFile(33445566, 128, $secondFilePath);
        $secondFile = \Safe\fopen($secondFilePath, 'r');
        $putFileResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            $secondFile,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($putFileResponse, false);
        unlink($secondFilePath);

        $etagAfterReplace = $this->getElementEtag($elementId);
        $this->assertNotSame($etagBeforeReplace, $etagAfterReplace);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testDeletingAFileUpdatesTheElement(): void
    {
        $elementResponse = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'file-delete-updates-element',
                ],
            ]
        );
        $elementId = $this->getUuidFromLocation($elementResponse);

        $filePath = __DIR__.'/../../Asset/file-delete-updates-element.bin';
        $this->generateDeterministicFile(44556677, 128, $filePath);
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

        $etagBeforeDelete = $this->getElementEtag($elementId);

        sleep(1);

        $deleteFileResponse = $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertIsDeletedResponse($deleteFileResponse);

        $etagAfterDelete = $this->getElementEtag($elementId);
        $this->assertNotSame($etagBeforeDelete, $etagAfterDelete);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
