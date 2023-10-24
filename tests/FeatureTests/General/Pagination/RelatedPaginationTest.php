<?php

namespace App\tests\FeatureTests\General\Pagination;

use App\Tests\FeatureTests\BaseRequestTestCase;

class RelatedPaginationTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    public const NODE_WITH_MANY_RELATED_ELEMENTS = '1ab54e88-a9cc-481a-b371-8873ca56c51b';
    public const NODE_WITH_TWO_RELATED_ELEMENTS = 'f461a898-36d6-48a3-98a8-0df163d87104';

    public function testNodeWithManyRelatedElements(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/related',
                self::NODE_WITH_MANY_RELATED_ELEMENTS
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(63, $data['totalNodes']);
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/related', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/related?page=2', $data['links']['next']);
        $this->assertSame('/1ab54e88-a9cc-481a-b371-8873ca56c51b/related?page=3', $data['links']['last']);
        $this->assertCount(25, $data['nodes']);
        $this->assertCount(25, $data['relations']);
    }

    public function testNodeWithTwoRelatedElements(): void
    {
        $response = $this->runGetRequest(
            sprintf(
                '/%s/related',
                self::NODE_WITH_TWO_RELATED_ELEMENTS
            ),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = json_decode((string) $response->getBody(), true);
        $this->assertSame(2, $data['totalNodes']);
        $this->assertSame('/f461a898-36d6-48a3-98a8-0df163d87104/related', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/f461a898-36d6-48a3-98a8-0df163d87104/related', $data['links']['last']);
        $this->assertCount(2, $data['nodes']);
        $this->assertCount(2, $data['relations']);
    }
}
