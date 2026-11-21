<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenIssuedWithoutExpirationDateTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:LsNu0vvb6iA69OAjAEH15a';
    private const string TOKEN_WITHOUT_EXPIRATION_DATE_UUID = '02IiNr0GOoKiPMMZemUzMmDIos0DwVKl0cBaH';
    private const string USER_UUID = '02IiNr0GOoKiPMLI6Cm2xuLbRlSlPhVx4ZayX';

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
        if (0 !== $result) {
            $this->fail(sprintf(
                'The following command is unsuccessful: %s',
                $command
            ));
        }

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_WITHOUT_EXPIRATION_DATE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
