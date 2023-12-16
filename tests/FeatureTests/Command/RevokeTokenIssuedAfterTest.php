<?php

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RevokeTokenIssuedAfterTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:IJA6RN4dKZENVerSXGp4H';
    public const TOKEN_ISSUED_AFTER_UUID = 'fbb02626-c3e8-46de-93d4-ee33607dbc69';
    public const USER_UUID = 'e1284efb-d66e-47fd-83b9-4c375ae1c9a2';

    public function testRevokeTokenIssuedAfter(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_AFTER_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        \Safe\exec(sprintf(
            'php bin/console token:revoke -f --user %s --issued-after "2021-01-01 00:00"',
            self::USER_UUID
        ));

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_AFTER_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
