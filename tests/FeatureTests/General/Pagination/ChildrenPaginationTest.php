<?php

namespace App\tests\FeatureTests\General\Pagination;

use App\Tests\FeatureTests\BaseRequestTestCase;

class ChildrenPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    private const string PARENT_WITH_MANY_CHILDREN = '1ab54e88-a9cc-481a-b371-8873ca56c51b';
    private const string PARENT_WITH_ONE_CHILD = '12ac1946-0efd-43b2-8e8f-fc73a2413b03';

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
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/children', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/children?page=2', $data['links']['next']);
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/children?page=3', $data['links']['last']);
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
        $this->assertSame('/12ac1946-0efd-43b2-8e8f-fc73a2413b03/children', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/12ac1946-0efd-43b2-8e8f-fc73a2413b03/children', $data['links']['last']);
        $this->assertCount(1, $data['nodes']);
        $this->assertCount(1, $data['relations']);
    }
}
