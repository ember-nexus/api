<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _04_03_GroupsPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:DXdhJefbNLWj8qrcfYZ8K2';

    private const string GROUP_1 = 'ae221c97-9f91-450c-bbb8-fce9f78f6c89';
    private const string GROUP_2 = '4995a840-23f3-4b06-a5ad-605dd21aa135';
    private const string GROUP_3 = '989775e2-7909-428c-87d5-91de5f193420';
    private const string DATA_1 = 'c01a334d-2801-408c-bbdf-c9542ae5edd9';
    private const string DATA_2 = 'deac8c82-12b8-4ee2-9916-317d2a8aeae8';
    private const string DATA_3 = '81873c2d-384f-4166-a7c3-991b33518b9b';

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
    }
}
