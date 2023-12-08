<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _04_02_GroupsImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:UlPeqBZJqgoaJFLvBKC26';

    public const GROUP_1 = 'bb05dead-3efb-47b9-b22b-12716eaefdb9';
    public const GROUP_2 = '78e0af0b-8657-43ae-93b9-5f3a88bf8664';
    public const GROUP_3 = '9133d23d-0a4c-4f7b-a1f4-d84587ae9b3c';
    public const DATA_1 = 'd79d90f5-7929-4ebd-8d06-1fa9a15aa63e';
    public const DATA_2 = '450b8457-641c-4685-a171-2c371ce7f3f0';
    public const IS_IN_GROUP_1 = '5f3bdada-0ae1-442d-a4b7-1a1f725c0a6e';
    public const IS_IN_GROUP_2 = 'c6eda8b4-bd6c-4df7-bf4a-41d2dc88e2cc';
    public const IS_IN_GROUP_3 = 'b74d5ae5-c534-4cf7-97a2-5190f8be8ba8';
    public const OWNS_DATA_1 = 'fd90f318-dc45-4432-a1a5-39b4e24d3b15';
    public const OWNS_DATA_2 = '3481bf4d-e393-4fd3-b0e9-131155495dbe';
    public const OWNS_TOKEN = '2bef8c63-b319-49a2-9431-dbe88b780e6a';
    public const RELATION = '933d3e3b-da81-4f3e-9320-122c71ddcfd9';

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
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_3), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
