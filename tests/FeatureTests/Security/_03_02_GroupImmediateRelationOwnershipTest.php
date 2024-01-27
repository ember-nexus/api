<?php

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _03_02_GroupImmediateRelationOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:JhQYvkR2HUHuukHbOgOuH1';

    private const string GROUP = 'f935bed2-cf13-406b-8ad2-0a943b9329f1';
    private const string DATA_1 = 'bc69b38c-d087-4355-97a9-4e4ec48e4810';
    private const string DATA_2 = '04cc4124-8b39-41ad-979c-e389e7c73fc6';
    private const string IS_IN_GROUP = 'c2e419ef-998c-48c6-8af3-e9db2b9bff17';
    private const string OWNS_TOKEN = '2643d165-8e0f-4978-b344-c360ce63ae41';
    private const string OWNS_DATA_1 = 'a8805631-5a83-49cb-af8b-628ff727b6d4';
    private const string OWNS_DATA_2 = '0bfd0925-75e1-4574-8cab-0e97267e7052';
    private const string RELATION = '704eac29-cda1-441f-93b4-069df785b9c2';

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
