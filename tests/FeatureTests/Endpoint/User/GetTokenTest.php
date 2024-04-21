<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetTokenTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:7s0vuDVJ1UDH0SErDeaLNe';

    public function testGetUserToken(): void
    {
        $tokenResponse = $this->runGetRequest('/token', self::TOKEN);
        $this->assertIsNodeResponse($tokenResponse, 'Token');

        $tokenUuid = $this->getBody($tokenResponse)['id'];

        $directTokenResponse = $this->runGetRequest(sprintf('/%s', $tokenUuid), self::TOKEN);
        $this->assertIsNodeResponse($directTokenResponse, 'Token');

        $this->assertSame((string) $tokenResponse->getBody(), (string) $directTokenResponse->getBody());
    }

    public function testGetAnonymousTokenFails(): void
    {
        $response = $this->runGetRequest('/token', null);
        $this->assertIsProblemResponse($response, 403);
    }

    public function testGetWrongTokenFails(): void
    {
        $response = $this->runGetRequest('/token', 'thisTokenDoesNotExist');
        $this->assertIsProblemResponse($response, 401);
    }
}
