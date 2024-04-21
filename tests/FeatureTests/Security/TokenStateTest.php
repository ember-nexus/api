<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class TokenStateTest extends BaseRequestTestCase
{
    private const string TOKEN_ACTIVE = 'secret-token:VWPtCCQskD6uQnf0CHdNjY';
    private const string TOKEN_REVOKED = 'secret-token:63b9ULc3WYvEOaIaXEfCNc';
    private const string TOKEN_EXPIRED = 'secret-token:JHU2FrEe7jshvFtrD6BRb3';
    private const string DATA_UUID = '048ecc31-0807-463c-a8db-989721c73f26';

    public function testAccessingApiWithActiveTokenWorks(): void
    {
        $indexResponse = $this->runGetRequest(sprintf('/%s', self::DATA_UUID), self::TOKEN_ACTIVE);
        $this->assertIsNodeResponse($indexResponse, 'Data');
    }

    public function testAccessingApiWithRevokedTokenDoesNotWork(): void
    {
        $indexResponse = $this->runGetRequest(sprintf('/%s', self::DATA_UUID), self::TOKEN_REVOKED);
        $this->assertIsProblemResponse($indexResponse, 401);
    }

    public function testAccessingApiWithExpiredTokenDoesNotWork(): void
    {
        $indexResponse = $this->runGetRequest(sprintf('/%s', self::DATA_UUID), self::TOKEN_EXPIRED);
        $this->assertIsProblemResponse($indexResponse, 401);
    }
}
