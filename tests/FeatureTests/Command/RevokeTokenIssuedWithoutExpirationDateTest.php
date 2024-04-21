<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenIssuedWithoutExpirationDateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:LsNu0vvb6iA69OAjAEH15a';
    private const string TOKEN_WITHOUT_EXPIRATION_DATE_UUID = 'ba4f017b-b1eb-453c-b79a-eafff2cefc69';
    private const string USER_UUID = '16eb1b2e-cf4b-4fc5-b595-c8dd64d3da75';

    public function testRevokeTokenIssuedWithoutExpirationDate(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITHOUT_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --user %s --issued-without-expiration-date',
            self::USER_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITHOUT_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
