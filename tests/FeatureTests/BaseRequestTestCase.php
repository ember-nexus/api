<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests;

use GuzzleHttp\Client;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class BaseRequestTestCase extends TestCase
{
    /**
     * @var string[] IGNORED_HEAD_HEADERS
     */
    private const array IGNORED_HEAD_HEADERS = ['X-Debug-Token', 'X-Debug-Token-Link', 'Date'];

    public function runGetRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        $headRequest = $this->runRequest('HEAD', $uri, $token, headers: $headers);
        $getRequest = $this->runRequest('GET', $uri, $token, headers: $headers);

        $headHeaders = $headRequest->getHeaders();
        $getHeaders = $getRequest->getHeaders();
        foreach ($headHeaders as $key => $value) {
            $this->assertArrayHasKey($key, $getHeaders);
            if (in_array($key, self::IGNORED_HEAD_HEADERS)) {
                continue;
            }
            $this->assertSame($value, $getHeaders[$key]);
        }

        return $getRequest;
    }

    public function runPostRequest(string $uri, ?string $token, array $data, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('POST', $uri, $token, $data, $headers);
    }

    public function runPutRequest(string $uri, ?string $token, array $data, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('PUT', $uri, $token, $data, $headers);
    }

    public function runPatchRequest(string $uri, ?string $token, array $data, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('PATCH', $uri, $token, $data, $headers);
    }

    public function runDeleteRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('DELETE', $uri, $token, headers: $headers);
    }

    public function runOptionsRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('OPTIONS', $uri, $token, headers: $headers);
    }

    public function runHeadRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('HEAD', $uri, $token, headers: $headers);
    }

    public function runCopyRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('COPY', $uri, $token, headers: $headers);
    }

    public function runLockRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('LOCK', $uri, $token, headers: $headers);
    }

    public function runMkcolRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('MKCOL', $uri, $token, headers: $headers);
    }

    public function runMoveRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('MOVE', $uri, $token, headers: $headers);
    }

    public function runPropfindRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('PROPFIND', $uri, $token, headers: $headers);
    }

    public function runProppatchRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('PROPPATCH', $uri, $token, headers: $headers);
    }

    public function runUnlockRequest(string $uri, ?string $token, ?array $headers = []): ResponseInterface
    {
        return $this->runRequest('UNLOCK', $uri, $token, headers: $headers);
    }

    public function runRequest(string $method, string $uri, ?string $token = null, ?array $data = null, ?array $headers = []): ResponseInterface
    {
        $client = new Client([
            'base_uri' => $_ENV['API_DOMAIN'],
            'http_errors' => false,
        ]);

        $options = [
            'headers' => $headers,
        ];
        if (null !== $token) {
            $options['headers']['Authorization'] = sprintf(
                'Bearer %s',
                $token
            );
        }

        if (null !== $data) {
            $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $options['body'] = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $client->request(
            $method,
            $uri,
            $options
        );
    }

    /**
     * @param resource $body
     */
    public function runUploadRequest(string $method, string $uri, $body, ?string $token = null, ?array $headers = []): ResponseInterface
    {
        $client = new Client([
            'base_uri' => $_ENV['API_DOMAIN'],
            'http_errors' => false,
        ]);

        $options = [
            'headers' => $headers,
            'body' => $body,
        ];
        if (null !== $token) {
            $options['headers']['Authorization'] = sprintf(
                'Bearer %s',
                $token
            );
        }

        return $client->request(
            $method,
            $uri,
            $options
        );
    }

    public function getBody(ResponseInterface $response): array
    {
        return \Safe\json_decode((string) $response->getBody(), true);
    }

    public function assertArrayHasNoNullValues(array $array): void
    {
        foreach ($array as $value) {
            $this->assertNotNull($value);
        }
    }

    public function assertIsCollectionResponse(ResponseInterface $response, ?int $countNodes = null, ?int $countRelations = null): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialCollection', $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertIsNumeric($body['totalNodes']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['nodes']);
        $this->assertIsArray($body['relations']);
        if ($countNodes) {
            $this->assertCount($countNodes, $body['nodes']);
        }
        if ($countRelations) {
            $this->assertCount($countRelations, $body['relations']);
        }
    }

    /**
     * @param string[] $elementIds
     */
    public function assertIsElementCollectionResponse(ResponseInterface $response, ?int $countElements = null, array $elementIds = []): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialElementCollection', $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertIsNumeric($body['totalElements']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['elements']);
        if ($countElements) {
            $this->assertCount($countElements, $body['elements']);
        }
        foreach ($elementIds as $elementId) {
            foreach ($body['elements'] as $element) {
                if ($element['id'] === $elementId) {
                    continue 2;
                }
            }
            $this->fail(sprintf(
                "Element with UUID '%s' not found.",
                $elementId
            ));
        }
    }

    public function assertIsSearchResultResponse(ResponseInterface $response): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_SearchResultResponse', $body['type']);
        $this->assertArrayHasKey('results', $body);
        $this->assertIsArray($body['results']);
    }

    public function assertIsNodeResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsRelationResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('start', $body);
        $this->assertArrayHasKey('end', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsBinaryStreamResponse(ResponseInterface $response, string $expectedMimeType): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $contentTypeHeaders = $response->getHeader('content-type');
        if (1 !== count($contentTypeHeaders)) {
            $this->fail(sprintf('Expected to find one content-type header in response, got %d.', count($contentTypeHeaders)));
        }
        $contentTypeHeader = $contentTypeHeaders[0];
        $responseMimeType = strtolower(explode(';', $contentTypeHeader)[0]);

        $this->assertSame(strtolower($expectedMimeType), $responseMimeType);
        $this->assertCount(1, $response->getHeader('Content-Disposition'));
    }

    public function assertIsProblemResponse(ResponseInterface $response, int $status): void
    {
        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame('application/problem+json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('type', $body);
        $this->assertStringStartsWith('http', $body['type']);
        $this->assertArrayHasKey('title', $body);
        if (array_key_exists('detail', $body)) {
            $this->assertNotEmpty($body['detail']);
        }
        $this->assertArrayHasKey('status', $body);
        $this->assertSame($status, $body['status']);

        $typeDetailResponse = $this->runGetRequest($body['type'], null);
        $this->assertSame(
            200,
            $typeDetailResponse->getStatusCode(),
            sprintf(
                'Expected error type detail page with URL %s to be available, got HTTP status code %d.',
                $body['type'],
                $typeDetailResponse->getStatusCode()
            )
        );
    }

    public function assertIsCreatedResponse(ResponseInterface $response, bool $requireLocation = true): void
    {
        $this->assertSame(201, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        if ($requireLocation) {
            $this->assertIsString($response->getHeader('Location')[0]);
        }
    }

    public function assertNoContentResponse(ResponseInterface $response, bool $hasHeader = false): void
    {
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertSame($hasHeader, $response->hasHeader('Location'));
    }

    public function assertNotModifiedResponse(ResponseInterface $response): void
    {
        $this->assertSame(304, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertFalse($response->hasHeader('Location'));
    }

    public function assertIsDeletedResponse(ResponseInterface $response): void
    {
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertFalse($response->hasHeader('Location'));
    }

    public function assertIsTextResponse(ResponseInterface $response, int $status): void
    {
        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame('text/plain; charset=utf-8', $response->getHeader('content-type')[0]);
    }

    public function assertHasSingleOwner(string $token, string $childId, string $parentId): void
    {
        $parentsResponse = $this->runGetRequest(
            sprintf('/%s/parents', $childId),
            $token
        );
        $this->assertIsCollectionResponse($parentsResponse);
        $parentsResponseData = json_decode((string) $parentsResponse->getBody(), true);
        $this->assertSame(1, $parentsResponseData['totalNodes']);
        $this->assertSame($parentId, $parentsResponseData['nodes'][0]['id']);
        $this->assertSame('OWNS', $parentsResponseData['relations'][0]['type']);
    }

    public function assertIsCreatedBy(string $token, string $nodeId, string $userId): void
    {
        $relatedResponse = $this->runGetRequest(
            sprintf('/%s/related', $nodeId),
            $token
        );
        $this->assertIsCollectionResponse($relatedResponse);
        $relatedResponseData = json_decode((string) $relatedResponse->getBody(), true);
        foreach ($relatedResponseData['relations'] as $relation) {
            if ('CREATED' === $relation['type']) {
                $this->assertSame($userId, $relation['start']);

                return;
            }
        }
        $this->fail(sprintf(
            'Unable to find CREATED relation for node with UUID %s.',
            $nodeId
        ));
    }

    public function assertIsTokenWithState(ResponseInterface $response, string $state): void
    {
        $this->assertIsNodeResponse($response, 'Token');
        $tokenBody = $this->getBody($response);
        $this->assertSame($state, $tokenBody['data']['state']);
    }

    public function getUuidFromLocation(ResponseInterface $response): string
    {
        $location = $response->getHeader('Location')[0];

        return array_reverse(explode('/', $location))[0];
    }

    public function generateDeterministicFile(int $seed, int $targetSize, string $outputPath): void
    {
        $lineWidth = 120;
        $chunkLines = 4096;
        $groupSize = 32;

        $fh = \Safe\fopen($outputPath, 'wb');
        mt_srand($seed);

        $written = 0;
        $state = (string) mt_rand(); // rolling state, re-seeded every $groupSize lines

        for ($counter = 0; $written < $targetSize; ++$counter) {
            // Re-seed state from mt_rand every $groupSize lines
            if (0 === $counter % $groupSize) {
                $state = hash('xxh128', (string) mt_rand().$counter);
            }

            // Roll state forward, build 4 × 32 = 128 hex chars, trim to 120
            $a = hash('xxh128', $state.$counter);
            $b = hash('xxh128', $a.$counter);
            $c = hash('xxh128', $b.$counter);
            $d = hash('xxh128', $c.$counter);
            $state = $d; // carry forward into next line / next group seed

            $line = substr($a.$b.$c.$d, 0, $lineWidth);

            // Buffer into chunks for efficient fwrite
            $chunk ??= '';
            $chunk .= $line."\n";

            if ($counter % $chunkLines === $chunkLines - 1 || $written + strlen($chunk) >= $targetSize) {
                if ($written + strlen($chunk) > $targetSize) {
                    $chunk = substr($chunk, 0, $targetSize - $written);
                }
                $written += fwrite($fh, $chunk);
                $chunk = '';
            }
        }

        \Safe\fclose($fh);
    }

    /**
     * @return string[]
     */
    public function splitFileToChunks(string $inputPath, int $chunkSize): array
    {
        if (!is_file($inputPath) || !is_readable($inputPath)) {
            throw new InvalidArgumentException(sprintf('File not readable: %s', $inputPath));
        }

        $uid = bin2hex(random_bytes(8));
        $handle = fopen($inputPath, 'rb');
        $index = 0;
        $paths = [];

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            if (false === $chunk || 0 === strlen($chunk)) {
                break;
            }

            $filename = sprintf('/tmp/upload-%s-%02d.part', $uid, $index);
            file_put_contents($filename, $chunk);
            $paths[] = $filename;
            ++$index;
        }

        fclose($handle);

        return $paths;
    }

    /**
     * @param string[] $paths
     */
    public function cleanupChunks(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
