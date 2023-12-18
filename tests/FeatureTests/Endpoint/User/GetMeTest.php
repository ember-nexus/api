<?php

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetMeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:S26Pn61Imv52pWOJ9GuXET';
    private const string INVALID_TOKEN = 'tokenDoesNotExist';

    public function testGetAnonymousMe(): void
    {
        $getAnonymousMeResponse = $this->runGetRequest('/me', null);
        $this->assertIsNodeResponse($getAnonymousMeResponse, 'User');

        $anonymousUserUuid = $this->getBody($getAnonymousMeResponse)['id'];

        $getAnonymousUserResponse = $this->runGetRequest(sprintf('/%s', $anonymousUserUuid), null);
        $this->assertIsNodeResponse($getAnonymousUserResponse, 'User');

        $this->assertSame((string) $getAnonymousMeResponse->getBody(), (string) $getAnonymousUserResponse->getBody());
    }

    public function testGetUserMe(): void
    {
        $getUserMeResponse = $this->runGetRequest('/me', self::TOKEN);
        $this->assertIsNodeResponse($getUserMeResponse, 'User');

        $userUuid = $this->getBody($getUserMeResponse)['id'];

        $getAnonymousUserResponse = $this->runGetRequest(sprintf('/%s', $userUuid), self::TOKEN);
        $this->assertIsNodeResponse($getAnonymousUserResponse, 'User');

        $this->assertSame((string) $getUserMeResponse->getBody(), (string) $getAnonymousUserResponse->getBody());
    }

    public function testGetUserMeWithInvalidTokenFails(): void
    {
        $response = $this->runGetRequest('/me', self::INVALID_TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }
}
