<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\Scenario01BasicNegativeTests;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _01_04_OwnsRelationshipsAreDirectionalTest extends BaseRequestTestCase
{
    private const string TOKEN_1 = 'secret-token:CqDtAdaXFchFHI4BAlaHZC';
    private const string TOKEN_2 = 'secret-token:BHaj5TtWZU7tJgEobfYvui';
    private const string USER_1 = '02IiNr0GOoKiPMLafFtHgnCSui6g50WN9HvOc';
    private const string USER_2 = '02IiNr0GOoKiPMN3bXNCbs1XS1a3CJtnxcR6C';
    private const string OWNS_1 = '02IiNr0GOoKiPMM8u5jt6QlPfPiDXMrrEWbPH';
    private const string OWNS_2 = '02IiNr0GOoKiPMMj1sH4mcg69aIfU6G5nkDix';
    private const string OWNS_3 = '02IiNr0GOoKiPMLjutaLmrMbRJwxvy4Rw0lFx';
    private const string DATA_1 = '02IiNr0GOoKiPMLbQPGdgcAwwnJrAsAtMKtqa';
    private const string DATA_2 = '02IiNr0GOoKiPMMD4d7QI46HnCeqkzTV2hXTj';

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
