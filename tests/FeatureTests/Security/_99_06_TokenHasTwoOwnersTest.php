<?php

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _99_06_TokenHasTwoOwnersTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:Fqd0dnLYhXdPSMvMi3c8Bp';

    public const DATA = 'd69f2e6c-ce6a-4915-a567-acf0c1fc4432';
    public const TOKEN_UUID = 'bbf1baf9-c665-4470-9a1d-47483da18bf3';
    public const USER_1 = '3b9bd45a-a363-434f-8956-2877857c7456';
    public const USER_2 = '9827facc-40b0-40ca-a7a5-ff947e1c4b86';

    public function testGetDataFails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }

    public function testGetTokenFails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_UUID), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }

    public function testGetUser1Fails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }

    public function testGetUser2Fails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }
}
