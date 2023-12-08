<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _03_01_GroupImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:7RXVmoiChLrKPUJNjdEZvT';

    public const GROUP = 'f95c84f8-c33a-410a-93be-da0d65c1f0de';
    public const DATA = '56acb0c6-aaed-451f-b277-bceac61d7632';
    public const IS_IN_GROUP = 'f656b91e-6c2f-4dda-a4ff-69717e0b4559';
    public const OWNS_DATA = 'c76a7d30-2250-4bb3-bffd-e5f83a2ccd5b';
    public const OWNS_TOKEN = '2541a4d1-73f1-4716-8de8-2caf3c943d15';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }
}
