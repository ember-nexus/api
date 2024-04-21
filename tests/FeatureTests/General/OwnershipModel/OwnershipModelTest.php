<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\OwnershipModel;

use App\Tests\FeatureTests\BaseRequestTestCase;

class OwnershipModelTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:INirKZbnZETk6hLD86rfrL';

    private const string USER_UUID = 'e5d6c437-e13c-4fe8-85be-fc12e164eb17';
    private const string GROUP_1_UUID = '3d7bf35e-0188-467f-8cb4-6741a1741429';
    private const string GROUP_2_UUID = 'dbc96de1-0cb7-4f20-9cfe-d23740af32b0';
    private const string DATA_UUID = 'b66de74d-791a-4157-a632-4fdf066e80f2';
    private const string TOKEN_UUID = 'fdc9fc1f-1436-437b-a46d-358dbbb086d1';

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
