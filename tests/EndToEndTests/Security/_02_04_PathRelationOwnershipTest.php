<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _02_04_PathRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:JbPPgmVodPsBPGOpbS2NdD';

    private const string DATA_1 = '02IiNr0GOoKiPMLaqbdkFAaR25gNFUgTZYGAc';
    private const string DATA_2 = '02IiNr0GOoKiPMMy2FBZFNj9uxyxWlR4fEETJ';
    private const string DATA_3 = '02IiNr0GOoKiPMMRYmOJFm6k5SgMDth6EGOBm';
    private const string DATA_4 = '02IiNr0GOoKiPMLBtETEs6kuL5MT2u7F7mNpA';
    private const string DATA_5 = '02IiNr0GOoKiPMLt14Z4GCDPVXEAHafpc1DgD';
    private const string DATA_6 = '02IiNr0GOoKiPMLIhspHSTDGG0NdPnuvzj5lZ';

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
