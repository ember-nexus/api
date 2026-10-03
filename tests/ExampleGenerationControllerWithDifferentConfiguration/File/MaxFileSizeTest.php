<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDifferentConfiguration\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Runs with `file.maxFileSizeInBytes` set to 1024 bytes, see configuration.yml in the parent directory. Must be run
 * with `composer test:example-generation-controller:with-different-configuration`, otherwise the default limit of
 * 10 GiB applies and the tests fail.
 *
 * Because the limit is smaller than the minimum chunk size, the running-total check of a resumable upload can only be
 * reached by the final chunk.
 */
class MaxFileSizeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int MAX_FILE_SIZE = 1024;

    private function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    /**
     * @param array<string, string|int> $headers
     */
    private function sendFile(string $method, string $elementId, int $size, array $headers = []): mixed
    {
        return $this->runUploadRequest(
            $method,
            sprintf('/%s/file', $elementId),
            str_repeat('a', $size),
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $headers)
        );
    }

    private function assertHasNoFile(string $elementId): void
    {
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);
    }

    public function testPostOversizedFileIsRejected(): void
    {
        $elementId = $this->createNode('max-file-size-post');

        $response = $this->sendFile('POST', $elementId, self::MAX_FILE_SIZE + 1);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString((string) self::MAX_FILE_SIZE, $this->getBody($response)['detail']);
        $this->assertHasNoFile($elementId);

        // exactly the limit is fine
        $this->assertIsCreatedResponse($this->sendFile('POST', $elementId, self::MAX_FILE_SIZE), false);
        $this->assertSame(self::MAX_FILE_SIZE, strlen((string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody()));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPutOversizedFileIsRejectedAndKeepsExistingFile(): void
    {
        $elementId = $this->createNode('max-file-size-put');
        $this->assertIsCreatedResponse($this->sendFile('POST', $elementId, 10), false);

        $response = $this->sendFile('PUT', $elementId, self::MAX_FILE_SIZE + 1);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString((string) self::MAX_FILE_SIZE, $this->getBody($response)['detail']);
        $this->assertSame(10, strlen((string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody()));

        $this->assertIsCreatedResponse($this->sendFile('PUT', $elementId, self::MAX_FILE_SIZE), false);
        $this->assertSame(self::MAX_FILE_SIZE, strlen((string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody()));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPostOversizedFileOnRelationIsRejected(): void
    {
        $relationId = $this->createEphemeralRelation(self::TOKEN, 'max-file-size-relation');

        $this->assertIsProblemResponse($this->sendFile('POST', $relationId, self::MAX_FILE_SIZE + 1), 400);
        $this->assertIsProblemResponse($this->sendFile('PUT', $relationId, self::MAX_FILE_SIZE + 1), 400);
        $this->assertHasNoFile($relationId);
        $this->assertIsCreatedResponse($this->sendFile('POST', $relationId, self::MAX_FILE_SIZE), false);

        $this->deleteEphemeralRelation(self::TOKEN, $relationId);
    }

    public function testOversizedUploadLengthIsRejectedOnUploadCreation(): void
    {
        $elementId = $this->createNode('max-file-size-upload-length');

        $response = $this->sendFile('POST', $elementId, 0, [
            'Upload-Complete' => '?0',
            'Upload-Length' => self::MAX_FILE_SIZE + 1,
        ]);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString((string) self::MAX_FILE_SIZE, $this->getBody($response)['detail']);

        // no upload was created
        $this->assertHasNoFile($elementId);

        // exactly the limit is fine
        $response = $this->sendFile('POST', $elementId, 0, [
            'Upload-Complete' => '?0',
            'Upload-Length' => self::MAX_FILE_SIZE,
        ]);
        $this->assertNoContentResponse($response, true);
        $this->runDeleteRequest(sprintf('/upload/%s', $this->getUuidFromLocation($response)), self::TOKEN);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPatchCrossingTheLimitIsRejected(): void
    {
        $elementId = $this->createNode('max-file-size-patch');
        $createResponse = $this->sendFile('POST', $elementId, 0, ['Upload-Complete' => '?0']);
        $this->assertNoContentResponse($createResponse, true);
        $uploadUrl = sprintf('/upload/%s', $this->getUuidFromLocation($createResponse));

        $patch = fn (int $size): mixed => $this->runUploadRequest(
            'PATCH',
            $uploadUrl,
            str_repeat('a', $size),
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );

        $response = $patch(self::MAX_FILE_SIZE + 1);
        $this->assertIsProblemResponse($response, 400);
        $this->assertStringContainsString((string) self::MAX_FILE_SIZE, $this->getBody($response)['detail']);

        // the upload was not advanced and no file exists
        $headResponse = $this->runHeadRequest($uploadUrl, self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame('0', $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
        $this->assertHasNoFile($elementId);

        // exactly the limit is fine
        $this->assertNoContentResponse($patch(self::MAX_FILE_SIZE));
        $this->assertSame(self::MAX_FILE_SIZE, strlen((string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody()));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }
}
