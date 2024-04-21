<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _02_04_PathRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:JbPPgmVodPsBPGOpbS2NdD';

    private const string DATA_1 = '3d6e975b-3c1e-40ea-a50d-21dc3a1df97b';
    private const string DATA_2 = 'ec5ea3d7-d192-4e96-8933-d03a1e16f978';
    private const string DATA_3 = 'a9a5ed76-755d-43ed-9a5d-8a1de910132c';
    private const string DATA_4 = '0a27046a-4ed6-4937-aa5e-418296ab8612';
    private const string DATA_5 = '62b6f1bf-25e3-43b5-b9c9-1920d62d77f6';
    private const string DATA_6 = '1822cdab-a246-4887-99f7-e03a0f62ce45';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
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
