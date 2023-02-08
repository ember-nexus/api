<?php

namespace App\Tests\FeatureTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class GetIndexTest extends TestCase
{
    public function testGetIndexWithoutTrailingSlash(): void
    {
        $client = new Client();
        $response = $client->get(sprintf(
            '%s',
            $_ENV['API_DOMAIN']
        ));
        $this->assertSame(200, $response->getStatusCode());

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialCollection', $body['@type']);
        $this->assertSame('/', $body['@id']);
        $this->assertIsNumeric($body['totalNodes']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['nodes']);
        $this->assertIsArray($body['relations']);
        $this->assertCount(0, $body['relations']);
    }

    public function testGetIndexWithTrailingSlash(): void
    {
        $client = new Client();
        $response = $client->get(sprintf(
            '%s/',
            $_ENV['API_DOMAIN']
        ));
        $this->assertSame(200, $response->getStatusCode());

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialCollection', $body['@type']);
        $this->assertSame('/', $body['@id']);
        $this->assertIsNumeric($body['totalNodes']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['nodes']);
        $this->assertIsArray($body['relations']);
        $this->assertCount(0, $body['relations']);
    }
}
