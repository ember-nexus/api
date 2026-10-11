<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;
use Laudis\Neo4j\ClientBuilder;
use Psr\Http\Message\ResponseInterface;

/**
 * Shape of the problem responses 409, 410, 412 and 416: type, title, status, request specific `instance` and the
 * camelCase extension members which help clients to recover. The situations which lead to them are covered in detail
 * by the endpoint tests of files and uploads.
 */
class ProblemResponsesTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ROSE_ID = '0fdd52ba-55da-430c-b015-3277a231e895';
    private const int ROSE_CONTENT_LENGTH = 138937;
    private const int CHUNK_SIZE = 5 * 1024 * 1024;

    /**
     * @return array<string, mixed>
     */
    private function assertProblemShape(ResponseInterface $response, int $status, string $expectedTypeSuffix, string $expectedTitle): array
    {
        $this->assertIsProblemResponse($response, $status);
        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertStringEndsWith($expectedTypeSuffix, $body['type']);
        $this->assertSame($expectedTitle, $body['title']);
        $this->assertSame($status, $body['status']);
        $this->assertMatchesRegularExpression(
            '/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            $body['instance']
        );
        // extension members are camelCase, never kebab-case
        foreach (array_keys($body) as $key) {
            $this->assertStringNotContainsString('-', (string) $key);
        }

        return $body;
    }

    private function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    private function putFile(string $elementId, string $content): ResponseInterface
    {
        return $this->runUploadRequest('PUT', sprintf('/%s/file', $elementId), $content, self::TOKEN, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename=problem.txt',
        ]);
    }

    private function createUpload(string $elementId): string
    {
        $response = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), str_repeat('a', self::CHUNK_SIZE), self::TOKEN, [
            'Upload-Complete' => '?0',
            'Content-Type' => 'application/octet-stream',
        ]);
        $this->assertNoContentResponse($response, true);

        return $this->getUuidFromLocation($response);
    }

    private function deleteNode(string $elementId): void
    {
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    public function testConflictOfWrongUploadOffsetContainsBothOffsets(): void
    {
        $elementId = $this->createNode('problem-409-offset');
        $uploadId = $this->createUpload($elementId);

        $response = $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), 'data', self::TOKEN, [
            'Upload-Complete' => '?0',
            'Upload-Offset' => 1234,
            'Content-Type' => 'application/partial-upload',
        ]);

        $body = $this->assertProblemShape($response, 409, '/error/409/conflict', 'Conflict');
        $this->assertSame(self::CHUNK_SIZE, $body['expectedOffset']);
        $this->assertSame(1234, $body['providedOffset']);

        $this->deleteNode($elementId);
    }

    public function testConflictOfCreatingFileOnElementWhichHasOne(): void
    {
        $elementId = $this->createNode('problem-409-has-file');
        $this->assertIsCreatedResponse($this->putFile($elementId, 'first'), false);

        $response = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), 'second', self::TOKEN, [
            'Content-Type' => 'text/plain',
        ]);

        $this->assertProblemShape($response, 409, '/error/409/conflict', 'Conflict');
        // the existing file was not touched
        $this->assertSame('first', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getBody());

        $this->deleteNode($elementId);
    }

    public function testGoneOfExpiredUpload(): void
    {
        $elementId = $this->createNode('problem-410');
        $uploadId = $this->createUpload($elementId);
        $result = ClientBuilder::create()
            ->withDriver('bolt', $_ENV['CYPHER_AUTH'])
            ->build()
            ->run("MATCH (u:Upload {id: \$id}) SET u.expires = datetime() - duration('PT1H') RETURN count(u) AS count", ['id' => $uploadId]);
        $this->assertSame(1, $result->first()->get('count'));

        $patchResponse = $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), 'data', self::TOKEN, [
            'Upload-Complete' => '?0',
            'Upload-Offset' => self::CHUNK_SIZE,
            'Content-Type' => 'application/partial-upload',
        ]);
        $this->assertProblemShape($patchResponse, 410, '/error/410/gone', 'Gone');

        // HEAD responses have no body, the status has to match
        $this->assertSame(410, $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN)->getStatusCode());

        $this->deleteNode($elementId);
    }

    public function testPreconditionFailedOfIfMatchOnElementWithoutFile(): void
    {
        $elementId = $this->createNode('problem-412-without-file');

        // an element without file has no file ETag, so no If-Match value can match
        $response = $this->runUploadRequest('PUT', sprintf('/%s/file', $elementId), 'content', self::TOKEN, [
            'Content-Type' => 'text/plain',
            'If-Match' => '"some-etag"',
        ]);

        $this->assertProblemShape($response, 412, '/error/412/precondition-failed', 'Precondition Failed');
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $this->deleteNode($elementId);
    }

    public function testPreconditionFailedOfStaleIfMatchOnDelete(): void
    {
        $elementId = $this->createNode('problem-412-stale');
        $this->assertIsCreatedResponse($this->putFile($elementId, 'content'), false);

        $response = $this->runDeleteRequest(sprintf('/%s/file', $elementId), self::TOKEN, ['If-Match' => '"stale-etag"']);

        $this->assertProblemShape($response, 412, '/error/412/precondition-failed', 'Precondition Failed');
        // the file is still there
        $this->assertSame(200, $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN)->getStatusCode());

        $this->deleteNode($elementId);
    }

    public function testRangeNotSatisfiableNamesTheLengthOfTheFile(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=999999999-']
        );

        $body = $this->assertProblemShape($response, 416, '/error/416/range-not-satisfiable', 'Range Not Satisfiable');
        $this->assertSame(self::ROSE_CONTENT_LENGTH, $body['totalLength']);
        $this->assertSame(999999999, $body['requestedStart']);
        $this->assertSame([sprintf('bytes */%d', self::ROSE_CONTENT_LENGTH)], $response->getHeader('Content-Range'));
    }

    public function testRangeNotSatisfiableOfEmptySuffixRange(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=-0']
        );

        $body = $this->assertProblemShape($response, 416, '/error/416/range-not-satisfiable', 'Range Not Satisfiable');
        $this->assertSame(self::ROSE_CONTENT_LENGTH, $body['totalLength']);
        $this->assertSame([sprintf('bytes */%d', self::ROSE_CONTENT_LENGTH)], $response->getHeader('Content-Range'));
    }
}
