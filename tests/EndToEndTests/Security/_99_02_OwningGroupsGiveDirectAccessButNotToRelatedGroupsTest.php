<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _99_02_OwningGroupsGiveDirectAccessButNotToRelatedGroupsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:SaUQctGYKDHdRnm1jj7pG5';

    private const string GROUP_1 = '02IiNr0GOoKiPMLnEaH3vcRafOG8kTaBPKxua';
    private const string GROUP_2 = '02IiNr0GOoKiPMLU3iwDRc1pnBHMyGlm9HItf';
    private const string GROUP_3 = '02IiNr0GOoKiPMMeHYr0dC5W63nJhUCwz5TD2';
    private const string DATA_1 = '02IiNr0GOoKiPMN3XiOJhwh7deLXuMwXLxKxY';
    private const string DATA_2 = '02IiNr0GOoKiPMLpDEDW7ORnt1RWrC5vYAGzF';
    private const string DATA_3 = '02IiNr0GOoKiPMLOSYvbNseT9wAmaD6nCLdhG';

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
    }

    public function testGetAllowedNodeFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA_1), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetForbiddenNodeFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA_3), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
