<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group command
 */
class RevokeTokenIssuedWithExpirationDateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:S1aCcsPd5nPfI16SGcoTY0';
    private const string TOKEN_WITH_EXPIRATION_DATE_UUID = '0b72c3eb-fea1-4436-a4d7-d1601da9b4ac';
    private const string USER_UUID = 'dcad1936-b490-45ba-b9cb-348b75b5f9f6';

    public function testRevokeTokenIssuedWithExpirationDate(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITH_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --user %s --issued-with-expiration-date',
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

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITH_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
