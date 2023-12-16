<?php

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenIssuedBeforeTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:A919tLukSQ5pGTdA5M30b5';
    public const TOKEN_ISSUED_BEFORE_UUID = '409eacbc-0f5e-43ff-9604-cafed272b63f';
    public const USER_UUID = '42c7c48b-8d0b-4b1f-aabf-8d12ec12851f';

    public function testRevokeTokenIssuedBefore(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_BEFORE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --user %s --issued-before "2022-01-01 00:00"',
            self::USER_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_BEFORE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
