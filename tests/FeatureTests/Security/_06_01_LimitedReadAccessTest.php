<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _06_01_LimitedReadAccessTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:S55SWBoCDt99LT95JTIWg0';

    public const GROUP_1 = 'fc7ddad0-d816-4243-a9e3-eb59ff02f119';
    public const GROUP_2 = 'ed2c5d71-8921-4229-9f37-1b8fe3c3d96e';
    public const GROUP_3 = 'c3a1868f-dc95-4c2d-bee7-8a6a7fc6a306';
    public const DATA_1 = '135a2d64-8c7a-41d5-9f4b-75fef057846a';
    public const DATA_2 = '7b08288b-9bd6-4acb-a20c-c75aacce613d';
    public const DATA_3 = '120bd9e8-8569-4256-bc18-4ca2cebaa73e';
    public const DATA_4 = 'abe4cba0-74af-4dc7-b38f-e4396a9e1bd6';
    public const DATA_5 = 'c91e6684-cc98-407d-af01-e94bc9420b9a';
    public const DATA_6 = 'ea60d6ea-1c30-4b8c-86f4-97a15c1a4938';
    public const DATA_7 = '26470e60-daf1-4225-8abb-30eb8d42188f';
    public const DATA_8 = '5fd5f92f-a144-4c89-ad16-f3562fb0f18c';
    public const DATA_9 = '4fc780e0-ad5f-431a-916e-52e24b38874a';
    public const RELATIONSHIP_1 = '70bdab5d-6539-4fc9-aa97-3196f5046b65';
    public const RELATIONSHIP_2 = '66ca9fe9-d293-48cf-829a-1ba8eee2ee4e';
    public const RELATIONSHIP_3 = 'b6fe1d8c-4e19-4359-8f51-dbfed781c513';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_7), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_8), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_9), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
