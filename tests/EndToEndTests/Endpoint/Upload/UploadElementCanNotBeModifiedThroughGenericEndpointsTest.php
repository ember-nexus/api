<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Upload;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `Upload` element holds internal state of the upload endpoints and must not be readable or modifiable
 * through the generic element endpoints.
 *
 * WARNING: today every request below answers 404 only because the upload owner has no access to the `Upload`
 * node itself; the forbidden-property check is only the second line of defense. Once global permissions are
 * introduced and users gain access to `Upload` nodes, `PUT`/`PATCH` are expected to answer 400 instead - a
 * different status code than that here is a reason to review the access rules, not just this test.
 */
class UploadElementCanNotBeModifiedThroughGenericEndpointsTest extends BaseUploadTestCase
{
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
        $elementId = $this->createElement(self::TOKEN, 'upload-generic-endpoints');
        $uploadId = $this->createUpload($elementId);

        $this->assertIsProblemResponse($this->runPutRequest(sprintf('/%s', $uploadId), self::TOKEN, $properties), 404);
        $this->assertIsProblemResponse($this->runPatchRequest(sprintf('/%s', $uploadId), self::TOKEN, $properties), 404);
        $this->assertUploadUntouched($uploadId);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testGetAndDeleteOfUploadElementAnswerNotFound(): void
    {
        $elementId = $this->createElement(self::TOKEN, 'upload-generic-endpoints');
        $uploadId = $this->createUpload($elementId);

        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s', $uploadId), self::TOKEN), 404);
        $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s', $uploadId), self::TOKEN), 404);
        $this->assertUploadUntouched($uploadId);

        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }
}
