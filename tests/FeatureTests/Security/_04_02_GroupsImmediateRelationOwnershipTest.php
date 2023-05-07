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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
