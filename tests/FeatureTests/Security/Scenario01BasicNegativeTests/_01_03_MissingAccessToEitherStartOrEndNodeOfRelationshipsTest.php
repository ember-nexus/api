<?php

namespace App\tests\FeatureTests\Security\Scenario01BasicNegativeTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_03_MissingAccessToEitherStartOrEndNodeOfRelationshipsTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:TNh7DjhjbAicQOmu2GuCZZ';
    public const USER = '4c40eb7d-7150-4e0d-983e-c4d57d5192f0';
    public const OWNS = '6c935653-2003-487d-bc4c-ff2d4a96d515';
    public const DATA_1 = '0400ece8-a5ec-4f2b-bf96-d110bc98e857';
    public const DATA_2 = '3bc1669a-016a-4f10-8bfd-984d341af1bb';
    public const DATA_3 = '15e4d734-dfd5-45a5-a941-59c26c0ca732';
    public const RELATION_1 = '2a47ff59-0979-4452-b766-16ce68e08a7e';
    public const RELATION_2 = '2a47ff59-0979-4452-b766-16ce68e08a7e';

    /**
     * @description test 1-03-01-01
     */
    public function test1030101(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 1-03-01-02
     */
    public function test1030102(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER), self::TOKEN);
        $this->assertIsNodeResponse($response, 'User');
    }

    /**
     * @description test 1-03-02-01
     */
    public function test1030201(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::OWNS), self::TOKEN);
        $this->assertIsRelationResponse($response, 'OWNS');
    }

    /**
     * @description test 1-03-03-01
     */
    public function test1030301(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_1), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }

    /**
     * @description test 1-03-04-01
     */
    public function test1030401(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-02
     */
    public function test1030402(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-03
     */
    public function test1030403(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-04
     */
    public function test1030404(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-05
     */
    public function test1030405(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::RELATION_1),
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
     * @description test 1-03-04-06
     */
    public function test1030406(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::RELATION_1),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-07
     */
    public function test1030407(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::RELATION_1),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-08
     */
    public function test1030408(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-20
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030420(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-21
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030421(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s/file', self::RELATION_1),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'I shall not exist.',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030422(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030423(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-24
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030424(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-30
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030430(): void
    {
        $response = $this->runCopyRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-31
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030431(): void
    {
        $response = $this->runLockRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-32
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030432(): void
    {
        $response = $this->runUnlockRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-33
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030433(): void
    {
        $response = $this->runMkcolRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-34
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030434(): void
    {
        $response = $this->runMoveRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-35
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030435(): void
    {
        $response = $this->runPropfindRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-04-36
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030436(): void
    {
        $response = $this->runProppatchRequest(sprintf('/%s', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-05-01
     */
    public function test1030501(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-01
     */
    public function test1030601(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-02
     */
    public function test1030602(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-03
     */
    public function test1030603(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-04
     */
    public function test1030604(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-05
     */
    public function test1030605(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::RELATION_2),
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
     * @description test 1-03-06-06
     */
    public function test1030606(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::RELATION_2),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-07
     */
    public function test1030607(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::RELATION_2),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-08
     */
    public function test1030608(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-20
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030620(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-21
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030621(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s/file', self::RELATION_2),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'I shall not exist.',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030622(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030623(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-24
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030624(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-30
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030630(): void
    {
        $response = $this->runCopyRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-31
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030631(): void
    {
        $response = $this->runLockRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-32
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030632(): void
    {
        $response = $this->runUnlockRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-33
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030633(): void
    {
        $response = $this->runMkcolRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-34
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030634(): void
    {
        $response = $this->runMoveRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-35
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030635(): void
    {
        $response = $this->runPropfindRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-06-36
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030636(): void
    {
        $response = $this->runProppatchRequest(sprintf('/%s', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 501);
    }

    /**
     * @description test 1-03-07-01
     */
    public function test1030701(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA_3), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
