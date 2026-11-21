<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _02_03_PathNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:6EorX1or81QkQDVp8uJMIV';

    private const string DATA_1 = '02IiNr0GOoKiPMLODVWcwzqoucL6oCOMVnkm1';
    private const string DATA_2 = '02IiNr0GOoKiPMLkSsy97p39iASRYhwbNzghE';
    private const string DATA_3 = '02IiNr0GOoKiPMLMuL4oQk1VXuLEVzGCmumR2';

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
    }
}
