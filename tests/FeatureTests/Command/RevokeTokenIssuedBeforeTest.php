<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group command
 */
class RevokeTokenIssuedBeforeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:A919tLukSQ5pGTdA5M30b5';
    private const string TOKEN_ISSUED_BEFORE_UUID = '409eacbc-0f5e-43ff-9604-cafed272b63f';
    private const string USER_UUID = '42c7c48b-8d0b-4b1f-aabf-8d12ec12851f';

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
        if ($result !== 0) {
            $this->fail(sprintf(
                "The following command is unsuccessful: %s",
                $command
            ));
        }

        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ISSUED_BEFORE_UUID), self::TOKEN);
        $this->assertIsTokenWithState($response, 'REVOKED');
    }
}
