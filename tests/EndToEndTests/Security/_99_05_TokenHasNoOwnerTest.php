<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _99_05_TokenHasNoOwnerTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:D44551NKEtvd9KgHXTPd32';

    private const string DATA = '02IiNr0GOoKiPMMxlB7C5Q8ZvioYmJIpYgmto';
    private const string TOKEN_UUID = '02IiNr0GOoKiPMMTZL8u3NHZ2TZuPwK5EtyQy';

    public function testGetDataFails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }

    public function testGetTokenFails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_UUID), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }
}
