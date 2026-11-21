<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class TokenDoesNotReturnInternalFieldsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:SaUQctGYKDHdRnm1jj7pG5';
    private const string TOKEN_ID = '02IiNr0GOoKiPMLvEs5EpbzfxhTgGA4FK8SY4';

    public function testTokenDoesNotReturnInternalFields(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::TOKEN_ID), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Token');
        $body = $this->getBody($response);
        $this->assertArrayNotHasKey('token', $body['data']);
        $this->assertArrayNotHasKey('hash', $body['data']);
        $this->assertArrayNotHasKey('_tokenHash', $body['data']);
    }
}
