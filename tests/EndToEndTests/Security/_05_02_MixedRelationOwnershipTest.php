<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _05_02_MixedRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:HGDfrDYh5T5hFerngpuY8I';

    private const string GROUP_1 = '02IiNr0GOoKiPMLIFZdCnEu6YLntrDHpDHvnO';
    private const string GROUP_2 = '02IiNr0GOoKiPMMIL04DJ7mzVncYxrlgGV0rS';
    private const string DATA_1 = '02IiNr0GOoKiPMN1GA8MAC8QYaY2jCj8WTdyM';
    private const string DATA_2 = '02IiNr0GOoKiPMLlaggg56miYmdod9GylJsjy';
    private const string DATA_3 = '02IiNr0GOoKiPMMXnfqXXWbsfPhyb7zKzbAfZ';
    private const string DATA_4 = '02IiNr0GOoKiPML7kZKm7xBJPFuNDffjqXBoM';
    private const string DATA_5 = '02IiNr0GOoKiPMLaiyfUlsH75znFGoLlJYSsH';
    private const string DATA_6 = '02IiNr0GOoKiPMLN1oYqf7FyJjNnVob0CpKE6';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_4), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_5), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_6), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
