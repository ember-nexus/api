<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `Upload` element holds internal state of the upload endpoints, e.g. the offset, the owner and the hash state of
 * the integrity check. It must not be readable or modifiable through the generic element endpoints.
 *
 * WARNING: today every request below answers 404, because the upload owner has no access to the `Upload` node itself
 * (it has no `OWNS` relation), so the generic endpoints behave as if the element did not exist. The
 * `UploadElementPropertyChangeEventListener` (400, forbidden property) is only the second line of defense.
 * Once global permissions are introduced, users may have access to `Upload` nodes: then these expectations have to be
 * re-checked and updated. For users with access to the node, `PUT`/`PATCH` are expected to answer 400 (forbidden
 * property); a different status code than 404 here is a reason to review the access rules, not just this test.
 */
class UploadElementCanNotBeModifiedThroughGenericEndpointsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function internalPropertiesProvider(): array
    {
        return [
            'hashState' => [['hashState' => 'abc']],
            'uploadOffset' => [['uploadOffset' => 0]],
            'uploadOwner' => [['uploadOwner' => '00000000-0000-4000-8000-000000000000']],
            'chunkIds' => [['chunkIds' => ['abc']]],
            'uploadComplete' => [['uploadComplete' => true]],
            'expires' => [['expires' => '2099-01-01T00:00:00+00:00']],
            'arbitrary property' => [['name' => 'changed']],
        ];
    }

    private function createNode(): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'upload-generic-endpoints'],
        ]));
    }

    private function createUpload(string $elementId): string
    {
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            str_repeat('a', self::CHUNK_SIZE),
            self::TOKEN,
            ['Upload-Complete' => '?0', 'Content-Type' => 'application/octet-stream']
        );
        $this->assertNoContentResponse($response, true);

        return $this->getUuidFromLocation($response);
    }

    private function assertUploadUntouched(string $uploadId): void
    {
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) self::CHUNK_SIZE, $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
    }

    /**
     * @param array<string, mixed> $properties
     */
    #[DataProvider('internalPropertiesProvider')]
    public function testPutAndPatchOfUploadElementAnswerNotFound(array $properties): void
    {
        $elementId = $this->createNode();
        $uploadId = $this->createUpload($elementId);

        $this->assertIsProblemResponse($this->runPutRequest(sprintf('/%s', $uploadId), self::TOKEN, $properties), 404);
        $this->assertIsProblemResponse($this->runPatchRequest(sprintf('/%s', $uploadId), self::TOKEN, $properties), 404);
        $this->assertUploadUntouched($uploadId);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testGetAndDeleteOfUploadElementAnswerNotFound(): void
    {
        $elementId = $this->createNode();
        $uploadId = $this->createUpload($elementId);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s', $uploadId), self::TOKEN), 404);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s', $uploadId), self::TOKEN), 404);
        $this->assertUploadUntouched($uploadId);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }
}
