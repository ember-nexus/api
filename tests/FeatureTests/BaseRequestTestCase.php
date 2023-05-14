<?php

namespace App\Tests\FeatureTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends TestCase
{
    public function runGetRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('GET', $uri, $token);
    }

    public function runPostRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('POST', $uri, $token);
    }

    public function runPutRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('PUT', $uri, $token);
    }

    public function runPatchRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('PATCH', $uri, $token);
    }

    public function runDeleteRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('DELETE', $uri, $token);
    }

    public function runOptionsRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('OPTIONS', $uri, $token);
    }

    public function runHeadRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('HEAD', $uri, $token);
    }

    public function runCopyRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('COPY', $uri, $token);
    }

    public function runLockRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('LOCK', $uri, $token);
    }

    public function runMkcolRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('MKCOL', $uri, $token);
    }

    public function runMoveRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('MOVE', $uri, $token);
    }

    public function runPropfindRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('PROPFIND', $uri, $token);
    }

    public function runProppatchRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('PROPPATCH', $uri, $token);
    }

    public function runUnlockRequest(string $uri, string $token): ResponseInterface
    {
        return $this->runRequest('UNLOCK', $uri, $token);
    }

    public function runRequest(string $method, string $uri, string $token): ResponseInterface
    {
        $client = new Client([
            'base_uri' => $_ENV['API_DOMAIN'],
            'http_errors' => false,
        ]);

        return $client->request(
            $method,
            $uri,
            [
                'headers' => [
                    'Authorization' => sprintf(
                        'Bearer %s',
                        $token
                    ),
                ],
            ]
        );
    }

    public function assertIsCollectionResponse(ResponseInterface $response): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json', $response->getHeader('content-type')[0]);

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
    }

    public function assertIsNodeResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsRelationResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('start', $body);
        $this->assertArrayHasKey('end', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsProblemResponse(ResponseInterface $response, int $status): void
    {
        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame('application/problem+json', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('type', $body);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayHasKey('type', $body);
        $this->assertSame($status, $body['status']);
    }
}
