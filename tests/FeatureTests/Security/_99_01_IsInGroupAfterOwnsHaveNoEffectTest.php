<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _99_01_IsInGroupAfterOwnsHaveNoEffectTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:AZuaUFmZ6YB2g14CO1qAlM';

    private const string GROUP_1 = 'a42beeb2-4c8f-4c31-ada8-fc1f127beb77';
    private const string GROUP_2 = '8a97b81c-56cb-4e09-a5e4-c80b7f6f2ee3';
    private const string DATA_1 = 'fbde7b0a-ed3b-4c19-bb95-2952a37feca1';
    private const string DATA_2 = '969e41af-a868-4026-b072-26791149f58b';
    private const string OWNS_TOKEN = '55a39b35-4f6d-4a98-b048-06352d201059';
    private const string IS_IN_GROUP_1 = '1b9c5238-335c-4023-af50-673178e7734f';
    private const string IS_IN_GROUP_2 = '0d105c9b-f801-44be-82dc-28559b0412b0';
    private const string OWNS_DATA_1 = 'bf3c3163-375f-41e6-b330-68fb2835a2b3';
    private const string OWNS_DATA_2 = 'ee0291eb-71c3-4402-b0f6-366254f57164';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
    }

    public function testGetForbiddenNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetForbiddenRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
