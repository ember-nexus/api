<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _03_01_GroupImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:7RXVmoiChLrKPUJNjdEZvT';

    private const string GROUP = '02IiNr0GOoKiPMN4MicX0KC7vxoiyxHA5aJMz';
    private const string DATA = '02IiNr0GOoKiPMLn8vfhFJnqnpxl6f2A5SqND';
    private const string IS_IN_GROUP = '02IiNr0GOoKiPMN2u6L8KccUxHDNXiTUlGbnK';
    private const string OWNS_DATA = '02IiNr0GOoKiPMMg30p6il6jpysS0IM6mq3Zp';
    private const string OWNS_TOKEN = '02IiNr0GOoKiPMLP68gWRmxdBJMz5XBnvjSIk';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNodes(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::GROUP), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Group');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedNodeFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
    }

    public function testGetAllowedRelations(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::IS_IN_GROUP), self::TOKEN);
        $this->assertIsRelationResponse($response, 'IS_IN_GROUP');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_TOKEN), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }
}
