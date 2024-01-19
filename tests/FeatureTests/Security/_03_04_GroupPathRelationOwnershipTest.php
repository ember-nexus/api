<?php

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _03_04_GroupPathRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8c0ciT8ILZPtRncR3qOfFE';

    private const string GROUP = '147684d6-b830-4193-a1e4-4ace37e29a55';
    private const string DATA_1 = 'dab654bd-89da-4698-8452-838fdfef5c98';
    private const string DATA_2 = 'fdbd8eab-fff4-49ea-b6e0-f943a553ce9e';
    private const string DATA_3 = '62940e0b-01c6-4495-ad73-b32aecacd994';
    private const string DATA_4 = 'bc90d5d7-08f5-4a73-986d-ae6b72a08c83';
    private const string DATA_5 = 'cb97d48d-40fd-4006-8956-1206181b163d';
    private const string DATA_6 = '9955c4a5-7d8c-4d94-8959-1c9055d496e1';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_4), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_5), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_6), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
