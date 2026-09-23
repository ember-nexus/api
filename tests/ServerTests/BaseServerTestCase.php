<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;
use Psr\Http\Message\ResponseInterface;
use Ramsey\Uuid\Uuid;

/**
 * Base for tests of the server behaviour (web server limits, PHP failures) against the production image, which is
 * started with lower limits by `bin/test-server`. The special responses are compared with the files in `docs/server/`,
 * set the environment variable FIX_CONTROLLER_OUTPUT to update them.
 */
abstract class BaseServerTestCase extends BaseRequestTestCase
{
    protected const string PATH_TO_ROOT = __DIR__.'/../../';
    protected const int FIRST_CHUNK_SIZE_IN_BYTES = 5 * 1024 * 1024;

    /**
     * @var string[]
     */
    private array $elementIdsToDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->elementIdsToDelete as $elementId) {
            $this->runDeleteRequest(sprintf('/%s', $elementId), $this->getToken());
        }
        $this->elementIdsToDelete = [];
        parent::tearDown();
    }

    protected function trackElementFromLocation(ResponseInterface $response): void
    {
        $this->elementIdsToDelete[] = $this->getUuidFromLocation($response);
    }

    protected function getToken(): string
    {
        return $_ENV['SERVER_TEST_TOKEN'];
    }

    protected function createElement(): string
    {
        $response = $this->runPostRequest('/', $this->getToken(), ['type' => 'Data', 'data' => ['name' => 'server test']]);
        $this->assertSame(201, $response->getStatusCode());
        $elementId = $this->getUuidFromLocation($response);
        $this->elementIdsToDelete[] = $elementId;

        return $elementId;
    }

    /**
     * Creates an element with a resumable upload, which already contains its first chunk of the minimum size.
     *
     * @return array{string, string} element id and upload id
     */
    protected function createElementWithResumableUpload(): array
    {
        $elementId = $this->createElement();
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            str_repeat('a', self::FIRST_CHUNK_SIZE_IN_BYTES),
            $this->getToken(),
            ['Upload-Complete' => '?0', 'Content-Type' => 'application/octet-stream']
        );
        $this->assertSame(204, $response->getStatusCode());

        return [$elementId, $this->getUuidFromLocation($response)];
    }

    /**
     * Starts the final chunk of a resumable upload with the given body length, the body has to be sent by the caller.
     */
    protected function startFinalChunkRequest(string $uploadId, int $bodyLength): RawHttpRequest
    {
        return new RawHttpRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            [
                'Authorization' => sprintf('Bearer %s', $this->getToken()),
                'Upload-Complete' => '?1',
                'Upload-Offset' => (string) self::FIRST_CHUNK_SIZE_IN_BYTES,
                'Content-Type' => 'application/partial-upload',
            ],
            $bodyLength
        );
    }

    protected function startCreateElementRequest(string $body): RawHttpRequest
    {
        return new RawHttpRequest(
            'POST',
            '/',
            [
                'Authorization' => sprintf('Bearer %s', $this->getToken()),
                'Content-Type' => 'application/json; charset=utf-8',
            ],
            strlen($body)
        );
    }

    protected function createElementBody(): string
    {
        return json_encode(['type' => 'Data', 'data' => ['name' => 'slow server test']], JSON_THROW_ON_ERROR);
    }

    /**
     * Every problem json response, also the ones which are created by the web server, identifies its request as
     * `instance` (`urn:uuid:<id>`); the id is also part of the logs (`requestId` of the application, `request_id` of
     * the web server).
     */
    protected function getInstanceOfProblemResponse(ResponseInterface $response): string
    {
        $this->assertGreaterThanOrEqual(400, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('instance', $body);
        $this->assertIsString($body['instance']);
        $this->assertMatchesRegularExpression('/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $body['instance']);
        // the id is not part of the response headers
        $this->assertFalse($response->hasHeader('X-Request-Id'));

        return $body['instance'];
    }

    /**
     * A cheap problem json response created by the application.
     */
    protected function runNotFoundRequest(): ResponseInterface
    {
        $response = $this->runGetRequest(sprintf('/%s', Uuid::uuid4()->toString()), $this->getToken());
        $this->assertSame(404, $response->getStatusCode());

        return $response;
    }

    protected function assertResponseMatchesDocumentation(string $directory, ResponseInterface $response, bool $ignoreTypeLine = false, bool $ignoreDetailLine = false): void
    {
        $this->getInstanceOfProblemResponse($response);
        $prefix = sprintf('docs/server/%s/%d-response-', $directory, $response->getStatusCode());
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $prefix.'header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $prefix.'body.json',
            $response,
            true,
            // the type of application errors contains the host name, the details of errors caused by truncated content
            // depend on the PHP version
            [...($ignoreTypeLine ? ['"type"'] : []), ...($ignoreDetailLine ? ['"detail"'] : [])]
        );
    }
}
