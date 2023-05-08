<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _02_02_ImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:Au6srY6s3cW5THS6LeCl9Z';

    public const OWNS_1 = '5eff5f2e-7dfb-487a-ab81-9bcef466388d';
    public const OWNS_2 = '5234732b-e1ab-4665-be47-10b9a903697f';
    public const RELATIONSHIP = 'fd9f4b9b-9831-4d8a-be98-a438f28e5038';
    public const DATA_1 = 'b1e85bf9-6a79-4e50-ae5a-ed49beac8cb5';
    public const DATA_2 = '76cc077f-580f-4a6f-ad33-170c545217d3';

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
    }

    public function testGetAllowedRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::RELATIONSHIP), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATIONSHIP');
    }
}
