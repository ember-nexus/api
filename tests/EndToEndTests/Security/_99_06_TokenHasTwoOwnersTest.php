<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _99_06_TokenHasTwoOwnersTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:Fqd0dnLYhXdPSMvMi3c8Bp';

    private const string DATA = '02IiNr0GOoKiPMMnRa6SV720hqjpWyJshCKn6';
    private const string TOKEN_UUID = '02IiNr0GOoKiPMMaTdDztkBfiygLQYTsVtgdK';
    private const string USER_1 = '02IiNr0GOoKiPMLZxrbI2OBY6FlpVQMxNUDOH';
    private const string USER_2 = '02IiNr0GOoKiPMMJ2RTenxTnk5QjRkiS9s90s';

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

    public function testGetUser1Fails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }

    public function testGetUser2Fails(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 401);
    }
}
