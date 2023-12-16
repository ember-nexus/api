<?php

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenIssuedWithExpirationDateTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:S1aCcsPd5nPfI16SGcoTY0';
    public const TOKEN_WITH_EXPIRATION_DATE_UUID = '0b72c3eb-fea1-4436-a4d7-d1601da9b4ac';
    public const USER_UUID = 'dcad1936-b490-45ba-b9cb-348b75b5f9f6';

    public function testRevokeTokenIssuedWithExpirationDate(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITH_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --user %s --issued-with-expiration-date',
            self::USER_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITH_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
