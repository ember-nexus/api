<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _05_01_MixedNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:FD6njLoO4k8cmFJbluNkfT';

    public const GROUP_1 = 'e849e075-5824-4bc1-9d37-bca918912e48';
    public const GROUP_2 = 'a6eef102-6982-40c9-b47d-670f078cdab4';
    public const DATA_1 = '81535f3e-20e8-46e8-b4c3-970391974cd7';
    public const DATA_2 = '29904788-ce0a-4989-bc84-81fe62bc59e2';
    public const DATA_3 = '012a7959-7a8f-4c3e-9ca1-a4bf2ac0637b';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
