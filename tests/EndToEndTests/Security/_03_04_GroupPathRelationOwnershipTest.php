<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _03_04_GroupPathRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8c0ciT8ILZPtRncR3qOfFE';

    private const string GROUP = '02IiNr0GOoKiPMLGuZycjgPkkEtBlkAp5LIgT';
    private const string DATA_1 = '02IiNr0GOoKiPMMpS3YIKvhBjoBbNr2bwX09O';
    private const string DATA_2 = '02IiNr0GOoKiPMN6UoYdcuorME9JQM0LmtcZ2';
    private const string DATA_3 = '02IiNr0GOoKiPMLsxHG9HlRaes5ocL2ROlfmc';
    private const string DATA_4 = '02IiNr0GOoKiPMMamUjsa7mHHJcsJyefIjzHV';
    private const string DATA_5 = '02IiNr0GOoKiPMMi5Q5tuuh0wkl1kmUWGFAXv';
    private const string DATA_6 = '02IiNr0GOoKiPMMJcHJxNRSSD7lEtLcRuhp3I';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
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
