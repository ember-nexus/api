<?php

namespace App\tests\FeatureTests\Security\Scenario01BasicNegativeTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group test
 */
class _01_01_NoConnectionTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:451NiderFjTRW7TpoLoSWA';
    public const USER = '33d44651-ba40-4e5e-b2c3-079b9509b068';
    public const DATA = 'b1b253a6-d9a5-45b5-b7a9-1be29ced4df6';

    /**
     * @description test 1-01-01-01
     */
    public function test1010101(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 1-01-01-02
     */
    public function test1010102(): void
    {
        $this->markTestIncomplete();
        $response = $this->runGetRequest(sprintf('/%s', self::USER), self::TOKEN);
        $this->assertIsNodeResponse($response, 'User');
    }

    /**
     * @description test 1-01-02-01
     */
    public function test1010201(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-02
     */
    public function test1010202(): void
    {
        $this->markTestIncomplete();
        $response = $this->runGetRequest(sprintf('/%s/parents', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-03
     */
    public function test1010203(): void
    {
        $this->markTestIncomplete();
        $response = $this->runGetRequest(sprintf('/%s/children', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-04
     */
    public function test1010204(): void
    {
        $this->markTestIncomplete();
        $response = $this->runGetRequest(sprintf('/%s/related', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-05
     */
    public function test1010205(): void
    {
        $this->markTestIncomplete();
        $response = $this->runPostRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-06
     */
    public function test1010206(): void
    {
        $this->markTestIncomplete();
        $response = $this->runPutRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-07
     */
    public function test1010207(): void
    {
        $this->markTestIncomplete();
        $response = $this->runPatchRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-08
     */
    public function test1010208(): void
    {
        $this->markTestIncomplete();
        $response = $this->runDeleteRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-20
     */
    public function test1010220(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-21
     */
    public function test1010221(): void
    {
        $response = $this->runPostRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-22
     */
    public function test1010222(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-23
     */
    public function test1010223(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-24
     */
    public function test1010224(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-30
     */
    public function test1010230(): void
    {
        $response = $this->runCopyRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-31
     */
    public function test1010231(): void
    {
        $response = $this->runLockRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-32
     */
    public function test1010232(): void
    {
        $response = $this->runUnlockRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-33
     */
    public function test1010233(): void
    {
        $response = $this->runMkcolRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-34
     */
    public function test1010234(): void
    {
        $response = $this->runMoveRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-35
     */
    public function test1010235(): void
    {
        $response = $this->runPropfindRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-01-02-36
     */
    public function test1010236(): void
    {
        $response = $this->runProppatchRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
