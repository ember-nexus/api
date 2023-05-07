<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _99_01_IsInGroupAfterOwnsHaveNoEffectTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:AZuaUFmZ6YB2g14CO1qAlM';

    public const GROUP_1 = 'a42beeb2-4c8f-4c31-ada8-fc1f127beb77';
    public const GROUP_2 = '8a97b81c-56cb-4e09-a5e4-c80b7f6f2ee3';
    public const DATA_1 = 'fbde7b0a-ed3b-4c19-bb95-2952a37feca1';
    public const DATA_2 = '969e41af-a868-4026-b072-26791149f58b';

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
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
