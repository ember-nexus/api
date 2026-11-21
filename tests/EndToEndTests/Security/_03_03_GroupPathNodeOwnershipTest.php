<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _03_03_GroupPathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:9SmOltFEeOc50tSJCFKiv';

    private const string GROUP = '02IiNr0GOoKiPMM9dORaIjoD4U6Tc8JoNxKhm';
    private const string DATA_1 = '02IiNr0GOoKiPMLh3RfpU9slVZxwpr8NEbFpq';
    private const string DATA_2 = '02IiNr0GOoKiPMN0Lc611Apx6cXlZYaxCgdL8';
    private const string DATA_3 = '02IiNr0GOoKiPMMncqexESFwAPR3S2Tfwg89V';

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
    }
}
