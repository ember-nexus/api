<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_03_MissingAccessToEitherStartOrEndNodeOfRelationshipsTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:TNh7DjhjbAicQOmu2GuCZZ';

    public const OWNS = '6c935653-2003-487d-bc4c-ff2d4a96d515';
    public const DATA_1 = '0400ece8-a5ec-4f2b-bf96-d110bc98e857';
    public const DATA_2 = '3bc1669a-016a-4f10-8bfd-984d341af1bb';
    public const RELATIONSHIP = '2a47ff59-0979-4452-b766-16ce68e08a7e';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetAllowedRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    public function testGetForbiddenRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATIONSHIP), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
