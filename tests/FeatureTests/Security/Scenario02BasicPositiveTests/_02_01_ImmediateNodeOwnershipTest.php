<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security\Scenario02BasicPositiveTests;

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

    /**
     * @description test 2-01-03-20
     *
     * @todo refactor for v0.2.0
     */
    public function test2010320(): void
    {
        $this->markTestSkipped();
        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 2-01-03-21
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test2010321(): void
    {
        $this->markTestSkipped();
        $response = $this->runPostRequest(
            sprintf('/%s/file', self::DATA),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'I shall not exist.',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 2-01-03-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test2010322(): void
    {
        $this->markTestSkipped();
        $response = $this->runPutRequest(sprintf('/%s/file', self::DATA), self::TOKEN, []);
    }

    /**
     * @description test 2-01-03-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test2010323(): void
    {
        $this->markTestSkipped();
        $response = $this->runPatchRequest(sprintf('/%s/file', self::DATA), self::TOKEN, []);
    }

    /**
     * @description test 2-01-03-24
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010324(): void
    {
        $this->markTestSkipped();
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-30
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010330(): void
    {
        $this->markTestSkipped();
        $response = $this->runCopyRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-31
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010331(): void
    {
        $this->markTestSkipped();
        $response = $this->runLockRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-32
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010332(): void
    {
        $this->markTestSkipped();
        $response = $this->runUnlockRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-33
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010333(): void
    {
        $this->markTestSkipped();
        $response = $this->runMkcolRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-34
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010334(): void
    {
        $this->markTestSkipped();
        $response = $this->runMoveRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-35
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010335(): void
    {
        $this->markTestSkipped();
        $response = $this->runPropfindRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }

    /**
     * @description test 2-01-03-36
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test2010336(): void
    {
        $this->markTestSkipped();
        $response = $this->runProppatchRequest(sprintf('/%s', self::DATA), self::TOKEN);
    }
}
