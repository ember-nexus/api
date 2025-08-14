<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group deprecated
 */
class PostTokenOldTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:3tgEP9MhD81rkp3qiJcm1U';
    private const string EMAIL = 'user@postToken.user.endpoint.localhost.dev';
    private const string PASSWORD = '1234';

    public function testPostToken(): void
    {
        $getIndexResponse = $this->runGetRequest('/', self::TOKEN);
        $getIndexResponseCount = json_decode((string) $getIndexResponse->getBody(), true)['totalNodes'];

        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => self::PASSWORD,
            ]
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertSame('_TokenResponse', $body['type']);
        $this->assertArrayHasKey('token', $body);

        // test that new token works
        $getIndexResponseFromNewToken = $this->runGetRequest('/', $body['token']);
        $getIndexResponseFromNewTokenCount = json_decode((string) $getIndexResponseFromNewToken->getBody(), true)['totalNodes'];

        // test that new token is counted as new element
        $this->assertSame($getIndexResponseFromNewTokenCount, $getIndexResponseCount + 1);
    }

    public function testPostTokenWithLifetime(): void
    {
        $getIndexResponse = $this->runGetRequest('/', self::TOKEN);
        $getIndexResponseCount = json_decode((string) $getIndexResponse->getBody(), true)['totalNodes'];

        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => self::PASSWORD,
                'lifetime' => 3600,
            ]
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertSame('_TokenResponse', $body['type']);
        $this->assertArrayHasKey('token', $body);

        // test that new token works
        $getIndexResponseFromNewToken = $this->runGetRequest('/', $body['token']);
        $getIndexResponseFromNewTokenCount = json_decode((string) $getIndexResponseFromNewToken->getBody(), true)['totalNodes'];

        // test that new token is counted as new element
        $this->assertSame($getIndexResponseFromNewTokenCount, $getIndexResponseCount + 1);
    }

    public function testPostTokenWithLifetimeUnderMinimumGetsSetToMinimum(): void
    {
        $getIndexResponse = $this->runGetRequest('/', self::TOKEN);
        $getIndexResponseCount = json_decode((string) $getIndexResponse->getBody(), true)['totalNodes'];

        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => self::PASSWORD,
                'lifetime' => 0,
            ]
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertSame('_TokenResponse', $body['type']);
        $this->assertArrayHasKey('token', $body);

        // test that new token works
        $getIndexResponseFromNewToken = $this->runGetRequest('/', $body['token']);
        $getIndexResponseFromNewTokenCount = json_decode((string) $getIndexResponseFromNewToken->getBody(), true)['totalNodes'];

        // test that new token is counted as new element
        $this->assertSame($getIndexResponseFromNewTokenCount, $getIndexResponseCount + 1);
    }

    public function testPostTokenWithLifetimeAboveMaximumGetsSetToMaximum(): void
    {
        $getIndexResponse = $this->runGetRequest('/', self::TOKEN);
        $getIndexResponseCount = json_decode((string) $getIndexResponse->getBody(), true)['totalNodes'];

        $response = $this->runPostRequest(
            '/token',
            null,
            [
                'type' => 'Token',
                'user' => self::EMAIL,
                'password' => self::PASSWORD,
                'lifetime' => 100 * 365 * 24 * 3600, // 100 years
            ]
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertSame('_TokenResponse', $body['type']);
        $this->assertArrayHasKey('token', $body);

        // test that new token works
        $getIndexResponseFromNewToken = $this->runGetRequest('/', $body['token']);
        $getIndexResponseFromNewTokenCount = json_decode((string) $getIndexResponseFromNewToken->getBody(), true)['totalNodes'];

        // test that new token is counted as new element
        $this->assertSame($getIndexResponseFromNewTokenCount, $getIndexResponseCount + 1);
    }
}
