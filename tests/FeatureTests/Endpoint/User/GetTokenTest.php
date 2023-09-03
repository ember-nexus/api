<?php

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetTokenTest extends BaseRequestTestCase
{
    public const TOKEN_1 = 'secret-token:7s0vuDVJ1UDH0SErDeaLNe';
    public const TOKEN_2 = 'secret-token:6SXhgAHr76tTok79RCbdjk';

    public function testGetToken(): void
    {
        $response1 = $this->runGetRequest('/token', self::TOKEN_1);
        $this->assertIsCollectionResponse($response1);
        $tokenCount1 = json_decode($response1->getBody(), true)['totalNodes'];
        $this->assertSame(2, $tokenCount1);

        $response2 = $this->runGetRequest('/token', self::TOKEN_2);
        $this->assertIsCollectionResponse($response2);
        $tokenCount2 = json_decode($response2->getBody(), true)['totalNodes'];
        $this->assertSame(2, $tokenCount2);

        $this->assertSame((string) $response1->getBody(), (string) $response2->getBody());
    }
}
