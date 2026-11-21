<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _03_02_GroupImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:JhQYvkR2HUHuukHbOgOuH1';

    private const string GROUP = '02IiNr0GOoKiPMN4ItXXzYmDiblB2cG2nMNvM';
    private const string DATA_1 = '02IiNr0GOoKiPMMagtoWBEC9iyjKSlcDCmHcB';
    private const string DATA_2 = '02IiNr0GOoKiPML9GzWXjX55XsAzQj6wNVWWd';
    private const string IS_IN_GROUP = '02IiNr0GOoKiPMMdr6SN0KXuDNqNOKeEXyX7U';
    private const string OWNS_TOKEN = '02IiNr0GOoKiPMLPaLYVrC4yTrBUSGb6fWt1Q';
    private const string OWNS_DATA_1 = '02IiNr0GOoKiPMMR0lySYsMv8hUO3Y4t1sT54';
    private const string OWNS_DATA_2 = '02IiNr0GOoKiPMLClzzyG2Kepk9hPVPia5eAj';
    private const string RELATION = '02IiNr0GOoKiPMLzcbQo2hMhkGOj2i2ZOPPDW';

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
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
