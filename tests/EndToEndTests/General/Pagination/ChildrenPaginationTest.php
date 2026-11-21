<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Pagination;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class ChildrenPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    private const string PARENT_WITH_MANY_CHILDREN = '02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH';
    private const string PARENT_WITH_ONE_CHILD = '02IiNr0GOoKiPMLG1tkWF1bdsK24jkbZS1dlx';

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
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/children', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/children?page=2', $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/children?page=3', $data['links']['last']);
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
        $this->assertSame('/02IiNr0GOoKiPMLG1tkWF1bdsK24jkbZS1dlx/children', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMLG1tkWF1bdsK24jkbZS1dlx/children', $data['links']['last']);
        $this->assertCount(1, $data['nodes']);
        $this->assertCount(1, $data['relations']);
    }
}
