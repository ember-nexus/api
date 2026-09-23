<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Security\Scenario02BasicPositiveTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @SuppressWarnings(PHPMD.UnusedLocalVariable)
 */
class _02_01_ImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:P4VWKNQ2A6UaoaQgGSQXRB';
    private const string USER = '88f75d78-5ba2-42bc-8a46-4f20651cff2e';
    private const string OWNS = 'd9e9b864-81a6-4821-9b11-2a556e762860';
    private const string DATA = '7d051aaf-904b-4711-81d8-07067fa94d7e';

    /**
     * @description test 2-01-01-01
     */
    public function test2010101(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 2-01-01-02
     */
    public function test2010102(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER), self::TOKEN);
        $this->assertIsNodeResponse($response, 'User');
    }

    /**
     * @description test 2-01-02-01
     */
    public function test2010201(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    /**
     * @description test 2-01-03-01
     */
    public function test2010301(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 2-01-03-02
     */
    public function test2010302(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }

    /**
     * @description test 2-01-03-03
     */
    public function test2010303(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 0, 0);
    }

    /**
     * @description test 2-01-03-04
     */
    public function test2010304(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }

    /**
     * @description test 2-01-03-05
     */
    public function test2010305(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'I shall exist.',
                    'scenario' => '2-01',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $newUuid = $this->getUuidFromLocation($response);
        $checkResponse = $this->runGetRequest(sprintf('/%s', $newUuid), self::TOKEN);
        $this->assertIsNodeResponse($checkResponse, 'Data');

        $this->assertHasSingleOwner(self::TOKEN, $newUuid, self::DATA);
        $this->assertIsCreatedBy(self::TOKEN, $newUuid, self::USER);
    }

    /**
     * @description test 2-01-03-06
     */
    public function test2010306(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'name' => 'I shall be updated.',
            ]
        );
        $this->assertNoContentResponse($response);
    }

    /**
     * @description test 2-01-03-07
     */
    public function test2010307(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'name' => 'I shall be updated, again! :P',
            ]
        );
        $this->assertNoContentResponse($response);
    }

    /**
     * @description test 2-01-03-08
     */
    public function test2010308(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsDeletedResponse($response);
    }
}
