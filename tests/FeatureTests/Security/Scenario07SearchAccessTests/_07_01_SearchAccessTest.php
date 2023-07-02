<?php

namespace App\tests\FeatureTests\Security\Scenario07SearchAccessTests;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _07_01_SearchAccessTest extends BaseRequestTestCase
{
    public const TOKEN_1 = 'secret-token:Dp6FGPhJplengl8NSAI7vO';
    public const TOKEN_2 = 'secret-token:RDiFmL57a2igibaW21ZFoC';
    public const TOKEN_3 = 'secret-token:MJ04n655khrXMOraf9INcV';
    public const TOKEN_4 = 'secret-token:PfVAPFK1qmE5pqel39Ime8';
    public const TOKEN_5 = 'secret-token:Urhq1nO0p8jQnAZER721r5';
    public const TOKEN_6 = 'secret-token:S8Y7FhkJIotvKnejEcSmdW';
    public const TOKEN_7 = 'secret-token:OSeO4aXCZQorQ977P6XISP';
    public const DATA_1 = '3a3c2f8b-d1bd-40fd-b381-82de60539c9f';
    public const DATA_2 = 'c1bacef0-bdfc-4b13-b2fa-062bea9c0372';
    public const DATA_3 = '2cacaa15-8920-43d4-b885-f53a90035aef';
    public const DATA_4 = 'ef8a72b3-87d0-478c-a665-e134be8b8f7a';

    private function baseSearchTest(string $token): void
    {
        $response = $this->runPostRequest(
            '/search',
            $token,
            [
                'query' => [
                    'term' => [
                        'test.keyword' => '7-01',
                    ],
                ],
                'nodeTypes' => ['Data'],
            ]
        );

        $this->assertIsUnifiedCollectionResponse(
            $response,
            3,
            [
                self::DATA_1,
                self::DATA_2,
                self::DATA_3,
            ]
        );
    }

    /**
     * @description test 7-01-01-01
     */
    public function test7010101(): void
    {
        $this->baseSearchTest(self::TOKEN_1);
    }

    /**
     * @description test 7-01-02-01
     */
    public function test7010201(): void
    {
        $this->baseSearchTest(self::TOKEN_2);
    }

    /**
     * @description test 7-01-03-01
     */
    public function test7010301(): void
    {
        $this->baseSearchTest(self::TOKEN_3);
    }

    /**
     * @description test 7-01-04-01
     */
    public function test7010401(): void
    {
        $this->baseSearchTest(self::TOKEN_4);
    }

    /**
     * @description test 7-01-05-01
     */
    public function test7010501(): void
    {
        $this->baseSearchTest(self::TOKEN_5);
    }

    /**
     * @description test 7-01-06-01
     */
    public function test7010601(): void
    {
        $this->baseSearchTest(self::TOKEN_6);
    }

    /**
     * @description test 7-01-07-01
     */
    public function test7010701(): void
    {
        $this->baseSearchTest(self::TOKEN_7);
    }
}
