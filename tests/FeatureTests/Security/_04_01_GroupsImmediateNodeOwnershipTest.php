<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _04_01_GroupsImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:GVt8Z7QsZf6c8Yu6e1gnB8';

    public const GROUP_1 = '8978f6cb-9a55-4727-9df4-ebd2dafe1352';
    public const GROUP_2 = '99f40c22-1b11-48bf-b9f4-350cac7b1fdd';
    public const GROUP_3 = 'f1c7a5c3-5135-458f-b5a3-940335b90d31';
    public const DATA = '896b608e-0dba-4f5f-8b39-522bc3e079c1';
    public const IS_IN_GROUP_1 = '8c9d0790-7faa-4310-abab-8800fd2c14d4';
    public const IS_IN_GROUP_2 = '0b9cf8e7-6bf0-4697-adb5-65b7145b84c5';
    public const IS_IN_GROUP_3 = '60f2a995-4c11-4c6c-a16b-2da6edbcaf44';
    public const OWNS_DATA = '1ef6accb-650a-459e-87fb-3209f15baa51';
    public const OWNS_TOKEN = '5bb067b2-3e28-4f2d-b0c9-0aa306f1d15f';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
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
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }
}
