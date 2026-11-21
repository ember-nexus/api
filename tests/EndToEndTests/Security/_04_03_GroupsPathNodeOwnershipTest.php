<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _04_03_GroupsPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:DXdhJefbNLWj8qrcfYZ8K2';

    private const string GROUP_1 = '02IiNr0GOoKiPMMTkc5wASixFTZjxmJGNP8SP';
    private const string GROUP_2 = '02IiNr0GOoKiPMLgmV3qDjsJdT74aqIxDOjhe';
    private const string GROUP_3 = '02IiNr0GOoKiPMMJFeBcGewfrWChwT3kjxKcg';
    private const string DATA_1 = '02IiNr0GOoKiPMMcUEaacaicyc7AKRsnaY0Li';
    private const string DATA_2 = '02IiNr0GOoKiPMMrMyuV70TY4j87VxJKTiikV';
    private const string DATA_3 = '02IiNr0GOoKiPMM81Lulwc8HnZlZYULV8f5DT';

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
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
