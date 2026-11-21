<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\Scenario01BasicNegativeTests;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _01_02_NoRelevantConnectionTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1KLilQkUoSgY4BhbCQimec';
    private const string USER = '02IiNr0GOoKiPMMgRX8hqacSlgyAlheKe8Dd8';
    private const string DATA = '02IiNr0GOoKiPMLwoyFZ2FQ9DiWL4gjup9I99';
    private const string RELATION = '02IiNr0GOoKiPMMS6lFO348iOKbE1MLHnCudt';

    /**
     * @description test 1-02-01-01
     */
    public function test1020101(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    /**
     * @description test 1-02-01-02
     */
    public function test1020102(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::USER), self::TOKEN);
        $this->assertIsNodeResponse($response, 'User');
    }

    /**
     * @description test 1-02-02-01
     */
    public function test1020201(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-02
     */
    public function test1020202(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-03
     */
    public function test1020203(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-04
     */
    public function test1020204(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-05
     */
    public function test1020205(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::RELATION),
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
     * @description test 1-02-02-06
     */
    public function test1020206(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s', self::RELATION),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-07
     */
    public function test1020207(): void
    {
        $response = $this->runPatchRequest(
            sprintf('/%s', self::RELATION),
            self::TOKEN,
            [
                'name' => 'I shall not be updated.',
            ]
        );
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-08
     */
    public function test1020208(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-20
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1020220(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-21
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1020221(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s/file', self::RELATION),
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
     * @description test 1-02-02-22
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1020222(): void
    {
        $response = $this->runPutRequest(sprintf('/%s/file', self::RELATION), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-02-23
     *
     * @todo refactor in v0.2.0 with actual body content required
     */
    public function test1020223(): void
    {
        $response = $this->runPatchRequest(sprintf('/%s/file', self::RELATION), self::TOKEN, []);
        $this->assertIsProblemResponse($response, 405);
    }

    /**
     * @description test 1-02-02-24
     *
     * @todo refactor in v0.2.0 with actual request required
     */
    public function test1020224(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::RELATION), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    /**
     * @description test 1-02-03-01
     */
    public function test1020301(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
