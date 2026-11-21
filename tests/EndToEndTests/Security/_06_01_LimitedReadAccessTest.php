<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _06_01_LimitedReadAccessTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:S55SWBoCDt99LT95JTIWg0';

    private const string GROUP_1 = '02IiNr0GOoKiPMN5t4qa2WVuUyE7HOe5Aw9Od';
    private const string GROUP_2 = '02IiNr0GOoKiPMMyQl2vICPqCylk8nAvr8ojU';
    private const string GROUP_3 = '02IiNr0GOoKiPMMeDjBgKlgCFASNd5eIXvzH2';
    private const string DATA_1 = '02IiNr0GOoKiPMLGMdmqQk2ltxWdYHIQj5fq7';
    private const string DATA_2 = '02IiNr0GOoKiPMM4r78PQltn1Yrz4JeDHF3ml';
    private const string DATA_3 = '02IiNr0GOoKiPMLFj1iGMuoUCn3ZFRo2JfjLk';
    private const string DATA_4 = '02IiNr0GOoKiPMMSehb6WxP84YLM4LKxGvQf2';
    private const string DATA_5 = '02IiNr0GOoKiPMMgrzP5e5mMq4g2HjMDTb2hO';
    private const string DATA_6 = '02IiNr0GOoKiPMMx5e7I4dUDXIK16191lY5dk';
    private const string DATA_7 = '02IiNr0GOoKiPMLPaN2GSfmYxXwWROVd3RK5i';
    private const string DATA_8 = '02IiNr0GOoKiPMLrcGTRN1whbjnRGINWCnzwD';
    private const string DATA_9 = '02IiNr0GOoKiPMLjnK93S5OnoHNIdaEKUFklT';
    private const string RELATIONSHIP_1 = '02IiNr0GOoKiPMLzpnv3NvfotypNrTcsxWOTZ';
    private const string RELATIONSHIP_2 = '02IiNr0GOoKiPMLuzkkQNav7sHAaCfIHyrCZ0';
    private const string RELATIONSHIP_3 = '02IiNr0GOoKiPMMY2lUyTfUXgYwz4EWj6URIk';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_7), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_8), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_9), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
