<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenIssuedAfterTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:IJA6RN4dKZENVerSXGp4H';
    private const string TOKEN_ISSUED_AFTER_UUID = '02IiNr0FHIR60KylIzsLuPGHb91Mi6EaEr1LG';
    private const string USER_UUID = '02IiNr0GOoKiPMMsaQfKGfasPlNHNf2KU6ZJq';

    public function testRevokeTokenIssuedAfter(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_AFTER_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --user %s --issued-after "2021-01-01 00:00"',
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

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_AFTER_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
