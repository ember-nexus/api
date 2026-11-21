<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General\Pagination;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class RelatedPaginationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';
    private const string NODE_WITH_MANY_RELATED_ELEMENTS = '02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH';
    private const string NODE_WITH_TWO_RELATED_ELEMENTS = '02IiNr0GOoKiPMN1xZGNip9IkVoKeYOUkx4ee';

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
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/related', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/related?page=2', $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMLJxGehrr78XwJennHG1e4LH/related?page=3', $data['links']['last']);
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
        $this->assertSame('/02IiNr0GOoKiPMN1xZGNip9IkVoKeYOUkx4ee/related', $data['links']['first']);
        $this->assertSame(null, $data['links']['previous']);
        $this->assertSame(null, $data['links']['next']);
        $this->assertSame('/02IiNr0GOoKiPMN1xZGNip9IkVoKeYOUkx4ee/related', $data['links']['last']);
        $this->assertCount(2, $data['nodes']);
        $this->assertCount(2, $data['relations']);
    }
}
