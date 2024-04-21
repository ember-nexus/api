<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _99_05_TokenHasNoOwnerTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:D44551NKEtvd9KgHXTPd32';

    private const string DATA = 'ebc364c6-ba06-4e80-9491-9bcc091199c6';
    private const string TOKEN_UUID = 'adc8ae1e-d86c-4fe4-8a12-1fb7c485d360';

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
