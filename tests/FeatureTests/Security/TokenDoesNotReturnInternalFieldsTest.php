<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class TokenDoesNotReturnInternalFieldsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:SaUQctGYKDHdRnm1jj7pG5';
    private const string TOKEN_ID = '674dae86-a2cc-4137-a275-a3aca2e1d64f';

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
