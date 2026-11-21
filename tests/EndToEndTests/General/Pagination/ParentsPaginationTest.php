<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Pagination;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class ParentsPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    private const string CHILD_WITH_MANY_PARENTS = '02IiNr0GOoKiPMLQNSx0m6IeX6AADKroi5U6O';
    private const string CHILD_WITH_ONE_PARENT = '02IiNr0GOoKiPMMKWxUdpb7ogaMli4k5pUqRl';

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
        $this->assertSame('/02IiNr0GOoKiPMLQNSx0m6IeX6AADKroi5U6O/parents', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/02IiNr0GOoKiPMLQNSx0m6IeX6AADKroi5U6O/parents?page=2', $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMLQNSx0m6IeX6AADKroi5U6O/parents?page=3', $data['links']['last']);
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
        $this->assertSame('/02IiNr0GOoKiPMMKWxUdpb7ogaMli4k5pUqRl/parents', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMMKWxUdpb7ogaMli4k5pUqRl/parents', $data['links']['last']);
        $this->assertCount(1, $data['nodes']);
        $this->assertCount(1, $data['relations']);
    }
}
