<?php

namespace App\tests\FeatureTests\General\Pagination;

use App\Tests\FeatureTests\BaseRequestTestCase;

class ChildrenPaginationTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    public const PARENT_WITH_MANY_CHILDREN = '1ab54e88-a9cc-481a-b371-8873ca56c51b';
    public const PARENT_WITH_ONE_CHILD = '12ac1946-0efd-43b2-8e8f-fc73a2413b03';

    public function testParentWithManyChildrenPagination(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/children',
                self::PARENT_WITH_MANY_CHILDREN
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(62, $data['totalNodes']);
        $this->assertSame('/', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/?page=2', $data['links']['next']);
        $this->assertSame('/?page=3', $data['links']['last']);
        $this->assertCount(25, $data['nodes']);
        $this->assertCount(25, $data['relations']);
    }

    public function testParentWithOneChildPagination(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/children',
                self::PARENT_WITH_ONE_CHILD
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(1, $data['totalNodes']);
        $this->assertSame('/', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/', $data['links']['last']);
        $this->assertCount(1, $data['nodes']);
        $this->assertCount(1, $data['relations']);
    }
}
