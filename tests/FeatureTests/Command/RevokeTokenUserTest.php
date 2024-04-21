<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenUserTest extends BaseRequestTestCase
{
    private const string TOKEN_1_USER_1 = 'secret-token:BPZQtpPeHOpUZ8JPEZK8s8';
    private const string TOKEN_2_USER_1 = 'secret-token:MEPKIWtNvAQpcLuUuGQeuC';
    private const string TOKEN_1_USER_2 = 'secret-token:PjrG1LJdmkHedddDoDHXgT';
    private const string TOKEN_1_USER_1_UUID = '47efaba8-5f2c-474c-a52c-b9e1973d5baf';
    private const string TOKEN_2_USER_1_UUID = '51e33dee-46b9-4b28-a20f-23a6d88955c9';
    private const string TOKEN_1_USER_2_UUID = '9a9c44c6-1af7-4320-89eb-7e83825ca39a';
    private const string USER_1_UUID = '15572057-0605-482a-9903-115ea8243474';

    public function testRevokeTokenUser(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_1_UUID), self::TOKEN_1_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_2_USER_1_UUID), self::TOKEN_2_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_2_UUID), self::TOKEN_1_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --user %s',
            self::USER_1_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_1_UUID), self::TOKEN_1_USER_1);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_2_USER_1_UUID), self::TOKEN_2_USER_1);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_2_UUID), self::TOKEN_1_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');
    }
}
