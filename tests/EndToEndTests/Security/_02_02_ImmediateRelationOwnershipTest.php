<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _02_02_ImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:Au6srY6s3cW5THS6LeCl9Z';

    private const string OWNS_1 = '02IiNr0GOoKiPMLrBusOcxN5k4jJonJh3SWJH';
    private const string OWNS_2 = '02IiNr0GOoKiPMLkytRyx3FacYv7mCQY9AWz5';
    private const string RELATION = '02IiNr0GOoKiPMN6R3Mpq1mnlJDRPj3meVL49';
    private const string DATA_1 = '02IiNr0GOoKiPMMVZsUPg9N0p9S3b6Zyzlwee';
    private const string DATA_2 = '02IiNr0GOoKiPMM2mpQRAOik3UHzVZSq7QNv3';

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
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
