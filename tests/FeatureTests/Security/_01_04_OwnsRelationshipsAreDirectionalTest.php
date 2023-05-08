<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_04_OwnsRelationshipsAreDirectionalTest extends BaseRequestTestCase
{
    public const TOKEN_1 = 'secret-token:CqDtAdaXFchFHI4BAlaHZC';
    public const TOKEN_2 = 'secret-token:BHaj5TtWZU7tJgEobfYvui';

    public const OWNS_1 = '835986a7-f060-4348-b15a-6b419dbed940';
    public const OWNS_2 = 'cd822e3e-c61a-4d03-87fa-ede9ae267668';
    public const OWNS_3 = '500ab8dc-c8a0-485c-9ae0-6768c61eb55f';
    public const DATA_1 = '3e9787bd-7db6-4294-ba8e-ee6f50d2730f';
    public const DATA_2 = '8beca827-9319-493d-a28c-6b1358c2c135';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetAllowedNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_2);
        $this->assertIsNodeResponse($response, 'Data');
    }

    public function testGetAllowedRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN_1);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN_1);
        $this->assertIsRelationResponse($response, 'OWNS');
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_3), self::TOKEN_2);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetForbiddenRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_3), self::TOKEN_1);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }
}
