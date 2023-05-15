<?php

namespace App\tests\FeatureTests\Security\Scenario01BasicNegativeTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_04_OwnsRelationshipsAreDirectionalTest extends BaseRequestTestCase
{
    public const TOKEN_1 = 'secret-token:CqDtAdaXFchFHI4BAlaHZC';
    public const TOKEN_2 = 'secret-token:BHaj5TtWZU7tJgEobfYvui';
    public const USER_1 = '3d0aa99e-ae4f-422c-b8a8-ea010871ae8c';
    public const USER_2 = 'f7cb8f04-b641-48f5-ac2f-616ef9ab51ce';
    public const OWNS_1 = '835986a7-f060-4348-b15a-6b419dbed940';
    public const OWNS_2 = 'cd822e3e-c61a-4d03-87fa-ede9ae267668';
    public const OWNS_3 = '500ab8dc-c8a0-485c-9ae0-6768c61eb55f';
    public const DATA_1 = '3e9787bd-7db6-4294-ba8e-ee6f50d2730f';
    public const DATA_2 = '8beca827-9319-493d-a28c-6b1358c2c135';

    /**
     * @description test 1-04-01-01
     */
    public function test1040101(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 1-04-01-02
     */
    public function test1040102(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_1), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'User');
    }

    /**
     * @description test 1-04-02-01
     */
    public function test1040201(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN_1);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    /**
     * @description test 1-04-03-01
     */
    public function test1040301(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 1-04-04-01
     */
    public function test1040401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN_1);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    /**
     * @description test 1-04-05-01
     */
    public function test1040501(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_1);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 1-04-06-01
     */
    public function test1040601(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_3), self::TOKEN_1);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-07-01
     */
    public function test1040701(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_2), self::TOKEN_1);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-01-03
     */
    public function test1040103(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN_2);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 1-04-01-04
     */
    public function test1040104(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-02-02
     */
    public function test1040202(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-03-02
     */
    public function test1040302(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-04-02
     */
    public function test1040402(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_2), self::TOKEN_2);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-04-05-02
     */
    public function test1040502(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN_2);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 1-04-06-02
     */
    public function test1040602(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS_3), self::TOKEN_2);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    /**
     * @description test 1-04-07-02
     */
    public function test1040702(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER_2), self::TOKEN_2);
        $this->assertIsNodeResponse($response, 'User');
    }
}
