<?php

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _04_04_GroupsPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:OnfkWLnGBZWcrWNdYa6m7a';

    private const string GROUP_1 = '8e3de198-e3a0-4eb8-876d-018aa5843281';
    private const string GROUP_2 = '29941ee9-fdc8-4d85-810e-707c2e199ce9';
    private const string GROUP_3 = 'c943c9c5-4992-418a-80ff-bfa2e9089eab';
    private const string DATA_1 = '4d0dad16-56d1-4206-b7bb-312e6684f731';
    private const string DATA_2 = 'cb5317df-cb52-449a-97e7-c9b7f80f65d0';
    private const string DATA_3 = '1353ff2e-1bde-4162-ac26-d82167e6f708';
    private const string DATA_4 = '298ac7a6-3576-4ae4-9c4e-6785ec805be6';
    private const string DATA_5 = 'b5950a3d-3634-4935-8bbc-14fbf2d774ae';
    private const string DATA_6 = '1719ca0c-dc8e-4b7a-a6a1-31f5cf31e9f5';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_3), self::TOKEN);
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
