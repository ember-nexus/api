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

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        //        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_1), self::TOKEN);
        //        $this->assertIsNodeResponse($response, 'Data');
        //        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_2), self::TOKEN);
        //        $this->assertIsNodeResponse($response, 'Data');
        //        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_3), self::TOKEN);
        //        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
