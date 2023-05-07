<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _03_02_GroupImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:JhQYvkR2HUHuukHbOgOuH1';

    public const GROUP = 'f935bed2-cf13-406b-8ad2-0a943b9329f1';
    public const DATA_1 = 'bc69b38c-d087-4355-97a9-4e4ec48e4810';
    public const DATA_2 = '04cc4124-8b39-41ad-979c-e389e7c73fc6';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        //        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        //        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
