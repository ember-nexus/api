<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\OwnershipModel;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class OwnershipModelTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:INirKZbnZETk6hLD86rfrL';

    private const string USER_UUID = '02IiNr0GOoKiPMMurwNxC7NvToPaz13x4llgo';
    private const string GROUP_1_UUID = '02IiNr0GOoKiPMLasTQS0EupaOXTBuKWUasaj';
    private const string GROUP_2_UUID = '02IiNr0GOoKiPMMpy9pzZ6CHKZUu9A3IkanGX';
    private const string DATA_UUID = '02IiNr0GOoKiPMMXlmTFgypRfQq98MQ7LPYvo';
    private const string TOKEN_UUID = '02IiNr0GOoKiPMN6WfuwL9JPQDDyMhbE3dNjV';

    public function testIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 0);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_1_UUID);
        $this->assertSame($body['nodes'][1]['id'], self::TOKEN_UUID);
    }

    public function testUserChildren(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::USER_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::TOKEN_UUID);
    }

    public function testUserRelated(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::USER_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 2);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_1_UUID);
        $this->assertSame($body['nodes'][1]['id'], self::TOKEN_UUID);
    }

    public function testGroup1Children(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::GROUP_1_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 0, 0);
    }

    public function testGroup1Parents(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::GROUP_1_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 0, 0);
    }

    public function testGroup1Related(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::GROUP_1_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 2);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_2_UUID);
        $this->assertSame($body['nodes'][1]['id'], self::USER_UUID);
    }

    public function testGroup2Children(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::GROUP_2_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::DATA_UUID);
    }

    public function testGroup2Parents(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::GROUP_2_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 0, 0);
    }

    public function testGroup2Related(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::GROUP_2_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 2, 2);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_1_UUID);
        $this->assertSame($body['nodes'][1]['id'], self::DATA_UUID);
    }

    public function testDataParents(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::DATA_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_2_UUID);
    }

    public function testDataRelated(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::DATA_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);

        $body = $this->getBody($response);
        $this->assertSame($body['nodes'][0]['id'], self::GROUP_2_UUID);
    }
}
