<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _03_03_GroupPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:9SmOltFEeOc50tSJCFKiv';

    private const string GROUP = '84db789d-a8aa-4042-86f4-6e6aa0f59558';
    private const string DATA_1 = '4a2095a3-9908-456f-82ad-2c0b8f7ef240';
    private const string DATA_2 = 'f11927d2-e50a-4134-9920-be1d3fcfc3c7';
    private const string DATA_3 = 'd6f7bf30-cfb1-4875-a2b1-df1d982752a7';

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
    }
}
