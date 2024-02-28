<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group command
 */
class RevokeTokenIssuedWithoutExpirationDateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:LsNu0vvb6iA69OAjAEH15a';
    private const string TOKEN_WITHOUT_EXPIRATION_DATE_UUID = 'ba4f017b-b1eb-453c-b79a-eafff2cefc69';
    private const string USER_UUID = '16eb1b2e-cf4b-4fc5-b595-c8dd64d3da75';

    public function testRevokeTokenIssuedWithoutExpirationDate(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITHOUT_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --user %s --issued-without-expiration-date',
            self::USER_UUID
        );
        \Safe\exec(
            $command,
            result_code: $result
        );
        if ($result !== 0) {
            $this->fail(sprintf(
                "The following command is unsuccessful: %s",
                $command
            ));
        }

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITHOUT_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
