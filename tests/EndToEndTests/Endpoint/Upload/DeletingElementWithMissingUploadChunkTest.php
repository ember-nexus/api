<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Upload;

use App\Factory\S3ClientFactory;
use App\Tests\EndToEndTests\BaseRequestTestCase;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Psr\Log\NullLogger;

/**
 * An `Upload` element is deliberately not part of the reference dataset (see `node/general/consistency` there):
 * uploads are ephemeral by design (see `cron:delete-expired-uploads`), so a stray chunk missing from S3 is
 * reproduced dynamically here instead, by deleting it directly after it was uploaded.
 */
class DeletingElementWithMissingUploadChunkTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int FIRST_CHUNK_SIZE_IN_BYTES = 5 * 1024 * 1024;

    private function getS3Client(): S3Client
    {
        return (new S3ClientFactory(
            $_ENV['S3_ENDPOINT'],
            $_ENV['S3_ACCESS_KEY_ID'],
            $_ENV['S3_SECRET_ACCESS_KEY'],
            new NullLogger(),
            (new EmberNexusConfiguration())->setFileS3MaxHostConnections(16),
        ))->createS3Client();
    }

    private function deleteUploadChunkFromBucket(string $uploadId): void
    {
        $s3Client = $this->getS3Client();
        foreach ($s3Client->listObjectsV2(['Bucket' => 'api-upload']) as $object) {
            if (str_contains((string) $object->getKey(), $uploadId)) {
                $s3Client->deleteObject(['Bucket' => 'api-upload', 'Key' => (string) $object->getKey()]);
            }
        }
    }

    public function testDeletingElementWithUploadWhoseChunkIsMissingFromS3Succeeds(): void
    {
        $elementId = $this->createElement(self::TOKEN, 'deleting-element-with-missing-upload-chunk');

        $createUploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            str_repeat('a', self::FIRST_CHUNK_SIZE_IN_BYTES),
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createUploadResponse, true);
        $uploadId = $this->getUuidFromLocation($createUploadResponse);

        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));
        $this->deleteUploadChunkFromBucket($uploadId);
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($uploadId));

        $deleteElementResponse = $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);

        $this->assertIsDeletedResponse($deleteElementResponse);
    }
}
