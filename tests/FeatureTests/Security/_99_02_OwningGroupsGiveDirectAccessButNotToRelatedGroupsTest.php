<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _99_02_OwningGroupsGiveDirectAccessButNotToRelatedGroupsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:SaUQctGYKDHdRnm1jj7pG5';

    private const string GROUP_1 = '56dbdf19-8669-43a5-b332-3845e52e9cae';
    private const string GROUP_2 = '2f72bf0b-c6f2-45a9-9769-2c6dee5dfe02';
    private const string GROUP_3 = 'c3c99512-f29d-46d0-8d3f-37e89d6a079c';
    private const string DATA_1 = 'f7a4fff8-8e81-4eb5-8623-ae3d1a159642';
    private const string DATA_2 = '5aeaa647-39ef-419a-9c04-d12fd489f1c0';
    private const string DATA_3 = '23f86352-4382-4fbd-bc6b-848fa3eadae0';

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
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
