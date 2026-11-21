<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\Scenario01BasicNegativeTests;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _01_03_MissingAccessToEitherStartOrEndNodeOfRelationshipsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:TNh7DjhjbAicQOmu2GuCZZ';
    private const string USER = '02IiNr0GOoKiPMLi5bZ9mBqIdFomyOYKb76ra';
    private const string OWNS = '02IiNr0GOoKiPMLxpBhhN4alg5MA8xF3xsk3N';
    private const string DATA_1 = '02IiNr0GOoKiPML8uGRFnz5tK87NG6AlR3xeU';
    private const string DATA_2 = '02IiNr0GOoKiPMLa3RoNHgYBdEql2XXPDLPFV';
    private const string DATA_3 = '02IiNr0GOoKiPMLHby2891NfuaiLOkj4BXuTq';
    private const string RELATION_1 = '02IiNr0GOoKiPMLRX8yULf0XkIHsWbmjuFH1T';
    private const string RELATION_2 = '02IiNr0GOoKiPMLRX8yULf0XkIHsWbmjuFH1T';

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
        $this->assertIsProblemResponse($response, 404);
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
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030422(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-04-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030423(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 405);
    }

    /**
     * @description test 1-03-04-24
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1030424(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::RELATION_1), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
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
        $this->assertIsProblemResponse($response, 404);
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
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030622(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-03-06-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030623(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 405);
    }

    /**
     * @description test 1-03-06-24
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1030624(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::RELATION_2), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
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
