<?php

namespace App\Tests\FeatureTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends TestCase
{
    public function runGetRequest(string $uri, string $token): ResponseInterface
    {
        $client = new Client([
            'base_uri' => $_ENV['API_DOMAIN'],
            'http_errors' => false,
        ]);

        return $client->get(
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
