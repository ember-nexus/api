<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _02_01_ImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:P4VWKNQ2A6UaoaQgGSQXRB';

    public const OWNS = 'd9e9b864-81a6-4821-9b11-2a556e762860';
    public const DATA = '7d051aaf-904b-4711-81d8-07067fa94d7e';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }
}
