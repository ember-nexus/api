<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _05_01_MixedNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FD6njLoO4k8cmFJbluNkfT';

    private const string GROUP_1 = '02IiNr0GOoKiPMMw3YVWoW1Nus0pjKvg7rPSP';
    private const string GROUP_2 = '02IiNr0GOoKiPMMQDoqdn49ksIfLdBMnQyAIL';
    private const string DATA_1 = '02IiNr0GOoKiPMM7vfAkCsjYvy9WnYNFrN4Qg';
    private const string DATA_2 = '02IiNr0GOoKiPMLRCKX2Rl56AaMuoZ9ykufJQ';
    private const string DATA_3 = '02IiNr0GOoKiPML7VX9gilvlZBF6dYUgE3NED';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
