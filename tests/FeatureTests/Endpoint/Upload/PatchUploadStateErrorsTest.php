<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use ArrayObject;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use stdClass;

/**
 * PATCH errors which depend on the state stored for an upload: expired uploads (410), data exceeding the declared
 * Upload-Length (409) and an unusable stored hash state (409).
 *
 * The state which can not be reached through the API (a passed expiration date, a corrupted hash state) is
 * manipulated directly in the graph database, as the upload node is the single source of truth for it.
 */
class PatchUploadStateErrorsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    private function getCypherClient(): ClientInterface
    {
        return ClientBuilder::create()
            ->withDriver('bolt', $_ENV['CYPHER_AUTH'])
            ->build();
    }

    private function setUploadProperty(string $uploadId, string $propertyExpression): void
    {
        $result = $this->getCypherClient()->run(
            sprintf('MATCH (u:Upload {id: $id}) SET %s RETURN count(u) AS count', $propertyExpression),
            ['id' => $uploadId]
        );
        $this->assertSame(1, $result->first()->get('count'));
    }

    private function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    /**
     * @param array<string, string|int> $additionalHeaders
     */
    private function createUpload(string $elementId, string $body, array $additionalHeaders = []): string
    {
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $body,
            self::TOKEN,
            array_merge([
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ], $additionalHeaders)
        );
        $this->assertNoContentResponse($response, true);

        return $this->getUuidFromLocation($response);
    }

    private function patchUpload(string $uploadId, int $offset, string $body, bool $complete = true): mixed
    {
        return $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            $body,
            self::TOKEN,
            [
                'Upload-Complete' => $complete ? '?1' : '?0',
                'Upload-Offset' => $offset,
                'Content-Type' => 'application/partial-upload',
            ]
        );
    }

    private function assertUploadStillAtOffset(string $uploadId, int $offset): void
    {
        $headResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $headResponse->getStatusCode());
        $this->assertSame((string) $offset, $headResponse->getHeader('Upload-Offset')[0]);
        $this->assertSame('?0', $headResponse->getHeader('Upload-Complete')[0]);
    }

    private function cleanUp(string $uploadId, string $elementId): void
    {
        $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPatchOfExpiredUploadReturns410(): void
    {
        $elementId = $this->createNode('patch-upload-expired');
        $uploadId = $this->createUpload($elementId, '');
        $this->assertUploadStillAtOffset($uploadId, 0);

        $this->setUploadProperty($uploadId, "u.expires = datetime() - duration('PT1H')");

        $response = $this->patchUpload($uploadId, 0, 'some data');
        $this->assertIsProblemResponse($response, 410);

        // no file was created
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $this->cleanUp($uploadId, $elementId);
    }

    public function testPatchOfNotYetExpiredUploadSucceeds(): void
    {
        $elementId = $this->createNode('patch-upload-not-expired');
        $uploadId = $this->createUpload($elementId, '');

        // counterpart of the 410 test: shifting the expiration date into the future keeps the upload usable
        $this->setUploadProperty($uploadId, "u.expires = datetime() + duration('PT1H')");

        $this->assertNoContentResponse($this->patchUpload($uploadId, 0, 'some data'));
        $this->assertSame('some data', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPatchExceedingUploadLengthReturns409(): void
    {
        $elementId = $this->createNode('patch-upload-exceeds-length');
        $uploadId = $this->createUpload($elementId, '', ['Upload-Length' => 10]);
        $this->assertUploadStillAtOffset($uploadId, 0);

        $response = $this->patchUpload($uploadId, 0, str_repeat('a', 11));
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('upload length', $this->getBody($response)['detail']);

        // the request tried to complete the upload, which can never match its declared length: the upload is discarded
        $this->assertSame(404, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($uploadId));
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testPatchWithinUploadLengthSucceeds(): void
    {
        $elementId = $this->createNode('patch-upload-within-length');
        $uploadId = $this->createUpload($elementId, '', ['Upload-Length' => 10]);

        $this->assertNoContentResponse($this->patchUpload($uploadId, 0, str_repeat('a', 10)));
        $this->assertSame(str_repeat('a', 10), (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unusableHashStateProvider(): array
    {
        return [
            'not base64' => ['%%% not base64 %%%'],
            'base64 of no serialized data' => [base64_encode('this is not serialized data')],
            'serialized object of wrong class' => [base64_encode(serialize(new stdClass()))],
            'serialized scalar' => [base64_encode(serialize('string'))],
            // crafted states: unserialize() is restricted to HashContext (allowed_classes), so other classes are never
            // instantiated, not even as harmless objects; they end up as __PHP_Incomplete_Class and are rejected
            'serialized object of other class' => [base64_encode(serialize(new ArrayObject(['a' => 'b'])))],
            'serialized object of unknown class' => [base64_encode('O:22:"Evil\\NotExistingClass":1:{s:1:"a";i:1;}')],
            'serialized nested object of wrong class' => [base64_encode(serialize([new stdClass()]))],
        ];
    }

    #[DataProvider('unusableHashStateProvider')]
    public function testPatchWithUnusableStoredHashStateReturns409(string $hashState): void
    {
        $elementId = $this->createNode('patch-upload-broken-hash-state');
        // the first chunk stores the running hash state of the upload
        $uploadId = $this->createUpload($elementId, str_repeat('a', self::CHUNK_SIZE));
        $this->assertUploadStillAtOffset($uploadId, self::CHUNK_SIZE);

        $this->setUploadProperty($uploadId, sprintf("u.hashState = '%s'", $hashState));

        $response = $this->patchUpload($uploadId, self::CHUNK_SIZE, 'final chunk');
        $this->assertIsProblemResponse($response, 409);
        $this->assertStringContainsString('restart the upload', $this->getBody($response)['detail']);

        // the upload was not advanced and no file was created
        $this->assertUploadStillAtOffset($uploadId, self::CHUNK_SIZE);
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $this->cleanUp($uploadId, $elementId);
    }
}
