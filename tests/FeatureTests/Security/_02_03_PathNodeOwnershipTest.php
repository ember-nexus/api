<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _02_03_PathNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:6EorX1or81QkQDVp8uJMIV';

    public const DATA_1 = '237defce-d3be-44c5-b8a1-8288b76851e4';
    public const DATA_2 = '512e0d66-68bd-4515-b969-fa0eb739ea50';
    public const DATA_3 = '20ca0b80-9a92-4425-896d-bad6129f9c2e';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
