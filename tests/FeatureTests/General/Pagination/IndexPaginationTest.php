<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Pagination;

use App\Tests\FeatureTests\BaseRequestTestCase;

class IndexPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:3rB96odH2vmGnVi7qgUjMf';

    public function testIndexPaginationWithDefaultProperties(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(63, $data['totalNodes']);
        $this->assertSame('/', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/?page=2', $data['links']['next']);
        $this->assertSame('/?page=3', $data['links']['last']);
        $this->assertCount(25, $data['nodes']);
        $this->assertCount(0, $data['relations']);
    }

    public function testIndexPaginationWithCustomPageSize(): void
    {
        $response = $this->runGetRequest('/?page=2&pageSize=10', self::TOKEN);
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(63, $data['totalNodes']);
        $this->assertSame('/?pageSize=10', $data['links']['first']);
        $this->assertSame('/?pageSize=10', $data['links']['previous']);
        $this->assertSame('/?page=3&pageSize=10', $data['links']['next']);
        $this->assertSame('/?page=7&pageSize=10', $data['links']['last']);
        $this->assertCount(10, $data['nodes']);
        $this->assertCount(0, $data['relations']);
    }

    public function testIndexPaginationWithSinglePage(): void
    {
        $response = $this->runGetRequest('/?pageSize=100', self::TOKEN);
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(63, $data['totalNodes']);
        $this->assertSame('/?pageSize=100', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/?pageSize=100', $data['links']['last']);
        $this->assertCount(63, $data['nodes']);
        $this->assertCount(0, $data['relations']);
    }
}
