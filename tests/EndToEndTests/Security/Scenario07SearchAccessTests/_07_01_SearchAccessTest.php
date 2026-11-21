<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\Scenario07SearchAccessTests;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class _07_01_SearchAccessTest extends BaseRequestTestCase
{
    private const string TOKEN_1 = 'secret-token:Dp6FGPhJplengl8NSAI7vO';
    private const string TOKEN_2 = 'secret-token:RDiFmL57a2igibaW21ZFoC';
    private const string TOKEN_3 = 'secret-token:MJ04n655khrXMOraf9INcV';
    private const string TOKEN_4 = 'secret-token:PfVAPFK1qmE5pqel39Ime8';
    private const string TOKEN_5 = 'secret-token:Urhq1nO0p8jQnAZER721r5';
    private const string TOKEN_6 = 'secret-token:S8Y7FhkJIotvKnejEcSmdW';
    private const string TOKEN_7 = 'secret-token:OSeO4aXCZQorQ977P6XISP';
    private const string DATA_1 = '02IiNr0GOoKiPMLZILvqhhEsdhWyyXjlhaVGa';
    private const string DATA_2 = '02IiNr0GOoKiPMMdHIfRllzz6tOlRA5tejr9t';
    private const string DATA_3 = '02IiNr0GOoKiPMLSgsoe0WqlPI4wW6bBNOCg2';
    private const string DATA_4 = '02IiNr0GOoKiPMMzaRsWvdlD0Yjh4zaMbFC7b';

    private function baseSearchTest(string $token): void
    {
        $response = $this->runPostRequest(
            '/search',
            $token,
            [
                'steps' => [
                    [
                        'type' => 'elasticsearch-query-dsl-mixin',
                        'query' => [
                            'term' => [
                                'scenario.keyword' => 'security.limitedAccess.searchAccess',
                            ],
                        ],
                        'parameters' => [
                            'nodeTypes' => ['Data'],
                        ],
                    ],
                ],
            ]
        );

        $this->assertIsSearchResultResponse(
            $response
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
