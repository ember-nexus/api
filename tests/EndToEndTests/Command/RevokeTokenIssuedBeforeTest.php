<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenIssuedBeforeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:A919tLukSQ5pGTdA5M30b5';
    private const string TOKEN_ISSUED_BEFORE_UUID = '02IiNr0EPf9nVLAjizl3zbxLr6oAGFKwNLIVW';
    private const string USER_UUID = '02IiNr0GOoKiPMLdSprUloV0A5zH5s4LSrSw0';

    public function testRevokeTokenIssuedBefore(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_BEFORE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --user %s --issued-before "2022-01-01 00:00"',
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

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_BEFORE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
