<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Pagination;

use App\Tests\FeatureTests\BaseRequestTestCase;

class ParentsPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    private const string CHILD_WITH_MANY_PARENTS = '27ebaf04-8bd0-4c8b-9fce-c44f8f63312e';
    private const string CHILD_WITH_ONE_PARENT = '9b3f66d3-0078-472e-9c38-3cf7499a242a';

    public function testChildWithManyParentsPagination(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/parents',
                self::CHILD_WITH_MANY_PARENTS
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(62, $data['totalNodes']);
        $this->assertSame('/27ebaf04-8bd0-4c8b-9fce-c44f8f63312e/parents', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/27ebaf04-8bd0-4c8b-9fce-c44f8f63312e/parents?page=2', $data['links']['next']);
        $this->assertSame('/27ebaf04-8bd0-4c8b-9fce-c44f8f63312e/parents?page=3', $data['links']['last']);
        $this->assertCount(25, $data['nodes']);
        $this->assertCount(25, $data['relations']);
    }

    public function testChildWithOneParentPagination(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/parents',
                self::CHILD_WITH_ONE_PARENT
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(1, $data['totalNodes']);
        $this->assertSame('/9b3f66d3-0078-472e-9c38-3cf7499a242a/parents', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/9b3f66d3-0078-472e-9c38-3cf7499a242a/parents', $data['links']['last']);
        $this->assertCount(1, $data['nodes']);
        $this->assertCount(1, $data['relations']);
    }
}
