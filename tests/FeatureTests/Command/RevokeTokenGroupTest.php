<?php

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenGroupTest extends BaseRequestTestCase
{
    private const string GROUP_2_UUID = '6a8d7c70-59f4-4033-98ba-e9386cbda95f';

    private const string TOKEN_USER_1 = 'secret-token:LbN3tQcJK2p9Dj5uo8rNuW';
    private const string TOKEN_USER_2 = 'secret-token:2rjH18DE23Ak2cga8SAFu7';
    private const string TOKEN_USER_3 = 'secret-token:BmauTsaEvNrKt4pW2dSuDZ';
    private const string TOKEN_USER_4 = 'secret-token:8b6Pb2BmipspUNBgV9clfJ';
    private const string TOKEN_USER_5 = 'secret-token:GSJ2uHvVLhDq0XbjrfY1F4';
    private const string TOKEN_USER_1_UUID = '7aa23ea5-cc1e-4ac4-9397-08543e760bc2';
    private const string TOKEN_USER_2_UUID = 'c20ea18e-6988-4184-9414-a0f2457b8cc2';
    private const string TOKEN_USER_3_UUID = '598c7cbb-fdcc-4d1b-bb88-4d2032cc32a1';
    private const string TOKEN_USER_4_UUID = 'ef27b3bf-4f42-4838-911b-5de2f2dc545e';
    private const string TOKEN_USER_5_UUID = 'da78ce72-abf2-4ce9-ac4f-cfb555beb2f5';

    public function testRevokeTokenGroup(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_1_UUID), self::TOKEN_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_2_UUID), self::TOKEN_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_3_UUID), self::TOKEN_USER_3);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_4_UUID), self::TOKEN_USER_4);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_5_UUID), self::TOKEN_USER_5);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --group %s',
            self::GROUP_2_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_1_UUID), self::TOKEN_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_2_UUID), self::TOKEN_USER_2);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_3_UUID), self::TOKEN_USER_3);
        $this->assertIsProblemResponse($response, 401);

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_4_UUID), self::TOKEN_USER_4);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_5_UUID), self::TOKEN_USER_5);
        $this->assertIsTokenWithState($response, 'ACTIVE');
    }
}
