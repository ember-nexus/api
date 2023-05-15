<?php

namespace App\tests\FeatureTests\Security\Scenario02BasicPositiveTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * @group test
 */
class _02_01_ImmediateNodeOwnershipTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:P4VWKNQ2A6UaoaQgGSQXRB';
    public const USER = '88f75d78-5ba2-42bc-8a46-4f20651cff2e';
    public const OWNS = 'd9e9b864-81a6-4821-9b11-2a556e762860';
    public const DATA = '7d051aaf-904b-4711-81d8-07067fa94d7e';

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
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 2-01-02-02
     */
    public function test2010202(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }

    /**
     * @description test 2-01-02-03
     */
    public function test2010203(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 0, 0);
    }

    /**
     * @description test 2-01-02-04
     */
    public function test2010204(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::DATA), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }

    /**
     * @description test 2-01-02-05
     */
    public function test2010205(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'I shall exist.',
                    'test' => '2-01',
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

//    /**
//     * @description test 2-01-02-06
//     */
//    public function test2010206(): void
//    {
//        $response = $this->runPutRequest(
//            sprintf('/%s', self::DATA),
//            self::TOKEN,
//            [
//                'name' => 'I shall not be updated.',
//            ]
//        );
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-07
//     */
//    public function test2010207(): void
//    {
//        $response = $this->runPatchRequest(
//            sprintf('/%s', self::DATA),
//            self::TOKEN,
//            [
//                'name' => 'I shall not be updated.',
//            ]
//        );
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-08
//     */
//    public function test2010208(): void
//    {
//        $response = $this->runDeleteRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-20
//     */
//    public function test2010220(): void
//    {
//        $response = $this->runGetRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-21
//     *
//     * @todo refactor in v0.2.0 with actual body content required
//     */
//    public function test2010221(): void
//    {
//        $response = $this->runPostRequest(
//            sprintf('/%s/file', self::DATA),
//            self::TOKEN,
//            [
//                'type' => 'Data',
//                'data' => [
//                    'name' => 'I shall not exist.',
//                ],
//            ]
//        );
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-22
//     *
//     * @todo refactor in v0.2.0 with actual body content required
//     */
//    public function test2010222(): void
//    {
//        $response = $this->runPutRequest(sprintf('/%s/file', self::DATA), self::TOKEN, []);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-23
//     *
//     * @todo refactor in v0.2.0 with actual body content required
//     */
//    public function test2010223(): void
//    {
//        $response = $this->runPatchRequest(sprintf('/%s/file', self::DATA), self::TOKEN, []);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-24
//     */
//    public function test2010224(): void
//    {
//        $response = $this->runDeleteRequest(sprintf('/%s/file', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-30
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010230(): void
//    {
//        $response = $this->runCopyRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-31
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010231(): void
//    {
//        $response = $this->runLockRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-32
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010232(): void
//    {
//        $response = $this->runUnlockRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-33
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010233(): void
//    {
//        $response = $this->runMkcolRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-34
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010234(): void
//    {
//        $response = $this->runMoveRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-35
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010235(): void
//    {
//        $response = $this->runPropfindRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
//
//    /**
//     * @description test 2-01-02-36
//     *
//     * @todo refactor in v0.2.0 with actual request required
//     */
//    public function test2010236(): void
//    {
//        $response = $this->runProppatchRequest(sprintf('/%s', self::DATA), self::TOKEN);
//        $this->assertIsProblemResponse($response, 404);
//    }
}
