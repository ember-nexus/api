<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _04_01_GroupsImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:GVt8Z7QsZf6c8Yu6e1gnB8';

    private const string GROUP_1 = '02IiNr0GOoKiPMMBt0jLOKo10w1x53eKuicjH';
    private const string GROUP_2 = '02IiNr0GOoKiPMMJv8SRYQn8yNHRMIL7RGPDq';
    private const string GROUP_3 = '02IiNr0GOoKiPMN0gMK3MRZ189VZyhLGjkdVn';
    private const string DATA = '02IiNr0GOoKiPMMBr8qC3FklXE98igS1UOG4q';
    private const string IS_IN_GROUP_1 = '02IiNr0GOoKiPMMDPOCkYvhWhtwU7mlwrNj9m';
    private const string IS_IN_GROUP_2 = '02IiNr0GOoKiPMLCag11lpjj3Nx45V9VcZeAu';
    private const string IS_IN_GROUP_3 = '02IiNr0GOoKiPMLsACp44acPbF0i00AU60BJY';
    private const string OWNS_DATA = '02IiNr0GOoKiPMLM1al7kRK8m6BPfADbcfrmg';
    private const string OWNS_TOKEN = '02IiNr0GOoKiPMLpbgR46crlzEzM6tWcQeBq9';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_1), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_2), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP_3), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }
}
