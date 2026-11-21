<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenUserTest extends BaseRequestTestCase
{
    private const string TOKEN_1_USER_1 = 'secret-token:BPZQtpPeHOpUZ8JPEZK8s8';
    private const string TOKEN_2_USER_1 = 'secret-token:MEPKIWtNvAQpcLuUuGQeuC';
    private const string TOKEN_1_USER_2 = 'secret-token:PjrG1LJdmkHedddDoDHXgT';
    private const string TOKEN_1_USER_1_UUID = '02IiNr0GOoKiPMLfxcqP648hMHX1tDl6ObMJW';
    private const string TOKEN_2_USER_1_UUID = '02IiNr0GOoKiPMLkpRw8MscLOlqtR23WzFMzf';
    private const string TOKEN_1_USER_2_UUID = '02IiNr0GOoKiPMMKE48cz4OGMa0N2wzbAvuwo';
    private const string USER_1_UUID = '02IiNr0GOoKiPMLHL09AsYXpyYAQpSnczVhTc';

    public function testRevokeTokenUser(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_1_UUID), self::TOKEN_1_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_2_USER_1_UUID), self::TOKEN_2_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_2_UUID), self::TOKEN_1_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --user %s',
            self::USER_1_UUID
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

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_1_UUID), self::TOKEN_1_USER_1);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_2_USER_1_UUID), self::TOKEN_2_USER_1);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_1_USER_2_UUID), self::TOKEN_1_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');
    }
}
