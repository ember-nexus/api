<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenIssuedWithExpirationDateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:S1aCcsPd5nPfI16SGcoTY0';
    private const string TOKEN_WITH_EXPIRATION_DATE_UUID = '02IiNr0GOoKiPMLCWpMm3lBaHClRzRAzdeEDi';
    private const string USER_UUID = '02IiNr0GOoKiPMMqObPIUxPIpl108cImNrlNF';

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
        if (0 !== $result) {
            $this->fail(sprintf(
                'The following command is unsuccessful: %s',
                $command
            ));
        }

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITH_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
