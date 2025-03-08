<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Exception;

use App\Tests\FeatureTests\BaseRequestTestCase;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class MalformedJsonRequestTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';

    private function runRawPostRequest(string $uri, ?string $token = null, ?string $body = null, ?array $headers = []): ResponseInterface
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

        if (null !== $body) {
            $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $options['body'] = $body;
        }

        return $client->request(
            'POST',
            $uri,
            $options
        );
    }

    public function testSearchEndpointsHandlesMalformedJsonRequest(): void
    {
        $response = $this->runRawPostRequest(
            '/search',
            self::TOKEN,
            '{"key": [}'
        );

        $this->assertSame($response->getStatusCode(), 400);
        $this->assertIsProblemResponse($response, 400);
        $body = $this->getBody($response);
        $this->assertSame('Bad content', $body['title']);
        $this->assertSame('Unable to parse request as JSON. State mismatch (invalid or malformed JSON).', $body['detail']);
    }
}
