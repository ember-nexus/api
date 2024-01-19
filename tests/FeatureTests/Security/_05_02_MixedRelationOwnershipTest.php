<?php

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _05_02_MixedRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:HGDfrDYh5T5hFerngpuY8I';

    private const string GROUP_1 = '17322018-bf8d-4aff-aae1-e028bbdfbb16';
    private const string GROUP_2 = '96b251b5-56b6-43fa-9f62-dcd85892f0d3';
    private const string DATA_1 = 'f2f0fd3a-5d5f-4667-849d-06a3798614d6';
    private const string DATA_2 = '537bde6e-a661-4f31-b395-adc6bf3383c1';
    private const string DATA_3 = 'b67ebe02-0c3d-4c8f-bbb0-8bdf2fd57290';
    private const string DATA_4 = '01a23f36-fa76-4a9c-acfa-11803ebba906';
    private const string DATA_5 = '3d23a51c-861c-48c7-9233-7f8c27156400';
    private const string DATA_6 = '21003b34-684b-4736-a415-47e739cb9c24';

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
