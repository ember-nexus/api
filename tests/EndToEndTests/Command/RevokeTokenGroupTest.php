<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Command;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('command')]
class RevokeTokenGroupTest extends BaseRequestTestCase
{
    private const string GROUP_2_UUID = '02IiNr0GOoKiPMLwozaxNnUHN4G8ARkjG4seN';

    private const string TOKEN_USER_1 = 'secret-token:LbN3tQcJK2p9Dj5uo8rNuW';
    private const string TOKEN_USER_2 = 'secret-token:2rjH18DE23Ak2cga8SAFu7';
    private const string TOKEN_USER_3 = 'secret-token:BmauTsaEvNrKt4pW2dSuDZ';
    private const string TOKEN_USER_4 = 'secret-token:8b6Pb2BmipspUNBgV9clfJ';
    private const string TOKEN_USER_5 = 'secret-token:GSJ2uHvVLhDq0XbjrfY1F4';
    private const string TOKEN_USER_1_UUID = '02IiNr0GOoKiPMM4fkThnF4p2pgPBXJP3R2nj';
    private const string TOKEN_USER_2_UUID = '02IiNr0GOoKiPMMdQlNNiGJ7CvkRKmSyF4qdf';
    private const string TOKEN_USER_3_UUID = '02IiNr0GOoKiPMLoXjE3M3GKCDVW5osj1qd2J';
    private const string TOKEN_USER_4_UUID = '02IiNr0GOoKiPMMzP6fa7HaChGHn8dYj8F58P';
    private const string TOKEN_USER_5_UUID = '02IiNr0GOoKiPMMpKWiKv3kaOO5dcWK0lXQaw';

    public function testRevokeTokenGroup(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_1_UUID), self::TOKEN_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_2_UUID), self::TOKEN_USER_2);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_3_UUID), self::TOKEN_USER_3);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_4_UUID), self::TOKEN_USER_4);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_5_UUID), self::TOKEN_USER_5);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $result = 0;
        $command = sprintf(
            'php bin/console token:revoke -f --group %s',
            self::GROUP_2_UUID
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

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_1_UUID), self::TOKEN_USER_1);
        $this->assertIsTokenWithState($response, 'ACTIVE');

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_2_UUID), self::TOKEN_USER_2);
        $this->assertIsProblemResponse($response, 401);
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_3_UUID), self::TOKEN_USER_3);
        $this->assertIsProblemResponse($response, 401);

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_4_UUID), self::TOKEN_USER_4);
        $this->assertIsTokenWithState($response, 'ACTIVE');
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_USER_5_UUID), self::TOKEN_USER_5);
        $this->assertIsTokenWithState($response, 'ACTIVE');
    }
}
