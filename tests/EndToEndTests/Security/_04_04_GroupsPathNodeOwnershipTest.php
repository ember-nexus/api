<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _04_04_GroupsPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:OnfkWLnGBZWcrWNdYa6m7a';

    private const string GROUP_1 = '02IiNr0GOoKiPMMECSOVrmfhaOsnK98C85s0W';
    private const string GROUP_2 = '02IiNr0GOoKiPMLRCMHsYT0fHT7RX3qw3ZJ7E';
    private const string GROUP_3 = '02IiNr0GOoKiPMMgxZWyOux6WLVX380yb8LG1';
    private const string DATA_1 = '02IiNr0GOoKiPMLiSLIssyWzJSrKOcPIMEmFb';
    private const string DATA_2 = '02IiNr0GOoKiPMMhxpxaJH7Lph0qcFjssGvx8';
    private const string DATA_3 = '02IiNr0GOoKiPMLGMaxj2BToAaE1QfeiMxPfg';
    private const string DATA_4 = '02IiNr0GOoKiPMLRAWL2DQLmRnL9NRdyLg47l';
    private const string DATA_5 = '02IiNr0GOoKiPMMXNBWIS3iaZm6NoHx9uuiX2';
    private const string DATA_6 = '02IiNr0GOoKiPMLIBr9J1RJkU7it1BbHgoR1H';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_4), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_5), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_6), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
