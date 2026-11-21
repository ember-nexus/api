<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Sorting of Elasticsearch results with the property `sort` of the extended query definition.
 */
class SearchSortTest extends BaseSearchTestCase
{
    private const array PLANT_PARAMETERS = ['nodeTypes' => ['Plant'], 'pageSize' => 10];

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $parameters
     *
     * @return list<array<string, mixed>>
     */
    private function sortedSearch(mixed $sort, ?array $query = null, array $parameters = self::PLANT_PARAMETERS): array
    {
        $query ??= self::matchAll();

        return $this->getSearchElements($this->runElasticsearchStep(['query' => $query, 'sort' => $sort], $parameters));
    }

    /**
     * @param list<array<string, mixed>> $elements
     *
     * @return list<string>
     */
    private function getFirstSortValues(array $elements): array
    {
        return array_map(static fn (array $element): string => (string) $element['metadata']['sort'][0], $elements);
    }

    /**
     * @param list<string> $values
     */
    private function assertIsSortedAscending(array $values): void
    {
        $count = count($values);
        for ($i = 1; $i < $count; ++$i) {
            $this->assertLessThanOrEqual(0, strcmp($values[$i - 1], $values[$i]), sprintf("'%s' must not be sorted before '%s'.", $values[$i - 1], $values[$i]));
        }
    }

    public function testSortAscendingByKeywordField(): void
    {
        $elements = $this->sortedSearch([['name.keyword' => 'asc']]);

        $this->assertGreaterThan(3, count($elements));
        $this->assertIsSortedAscending($this->getFirstSortValues($elements));
    }

    public function testSortDescendingByKeywordFieldWithOptionsObject(): void
    {
        $elements = $this->sortedSearch([['name.keyword' => ['order' => 'desc']]]);

        $this->assertGreaterThan(3, count($elements));
        $this->assertIsSortedAscending(array_reverse($this->getFirstSortValues($elements)));
    }

    public function testSortAcceptsSingleObjectAndFieldNameShorthand(): void
    {
        $asObject = $this->sortedSearch(['name.keyword' => 'asc']);
        $asList = $this->sortedSearch([['name.keyword' => 'asc']]);
        $asString = $this->sortedSearch('name.keyword');

        $this->assertSame($this->getFirstSortValues($asList), $this->getFirstSortValues($asObject));
        $this->assertSame($this->getFirstSortValues($asList), $this->getFirstSortValues($asString));
    }

    public function testSortCanUseMultipleFields(): void
    {
        $elements = $this->sortedSearch([['name.keyword' => 'asc'], ['scientificName.keyword' => 'desc']]);

        foreach ($elements as $element) {
            $this->assertCount(2, $element['metadata']['sort']);
        }
        $this->assertIsSortedAscending($this->getFirstSortValues($elements));
    }

    public function testSortByScoreAscendingIsTheOppositeOfDescending(): void
    {
        $query = ['match' => ['description' => 'lily']];

        $ascending = $this->sortedSearch([['_score' => 'asc']], $query);
        $descending = $this->sortedSearch([['_score' => 'desc']], $query);

        $this->assertGreaterThan(2, count($ascending));
        $scores = array_map(static fn (array $element): float => $element['metadata']['score'], $descending);
        $sortedScores = $scores;
        rsort($sortedScores);
        $this->assertSame($sortedScores, $scores);
        $this->assertSame(
            array_map(static fn (array $element): string => $element['id'], array_reverse($descending)),
            array_map(static fn (array $element): string => $element['id'], $ascending)
        );
    }

    public function testScoreIsStillReturnedWhenSortingByAField(): void
    {
        $elements = $this->sortedSearch([['name.keyword' => 'asc']], ['match' => ['description' => 'lily']]);

        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            $this->assertIsFloat($element['metadata']['score']);
            $this->assertGreaterThan(0, $element['metadata']['score']);
        }
    }

    public function testMaxScoreIsStillReturnedWhenSortingByAField(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => ['match' => ['description' => 'lily']], 'sort' => [['name.keyword' => 'asc']]],
            self::PLANT_PARAMETERS
        );

        $this->assertIsSearchResultResponse($response);
        $results = $this->getBody($response)['results'];
        $this->assertIsFloat($results['maxScore']);
        $this->assertSame(
            max(array_map(static fn (array $element): float => $element['metadata']['score'], $results['elements'])),
            $results['maxScore']
        );
    }

    public function testMinScoreWorksTogetherWithSortingByAField(): void
    {
        $query = ['match' => ['description' => 'lily']];

        $unrestricted = $this->sortedSearch([['name.keyword' => 'asc']], $query);
        $minScore = 3.0;
        $response = $this->runElasticsearchStep(
            ['query' => $query, 'sort' => [['name.keyword' => 'asc']]],
            [...self::PLANT_PARAMETERS, 'minScore' => $minScore]
        );
        $restricted = $this->getSearchElements($response);

        $this->assertLessThan(count($unrestricted), count($restricted));
        $this->assertNotEmpty($restricted);
        foreach ($restricted as $element) {
            $this->assertGreaterThanOrEqual($minScore, $element['metadata']['score']);
        }
        $this->assertIsSortedAscending($this->getFirstSortValues($restricted));
    }

    public function testPaginationContinuesTheSortOrder(): void
    {
        $sort = [['name.keyword' => 'asc']];
        $pageOne = $this->getSearchElements($this->runElasticsearchStep(['query' => self::matchAll(), 'sort' => $sort], ['nodeTypes' => ['Plant'], 'pageSize' => 5, 'page' => 1]));
        $pageTwo = $this->getSearchElements($this->runElasticsearchStep(['query' => self::matchAll(), 'sort' => $sort], ['nodeTypes' => ['Plant'], 'pageSize' => 5, 'page' => 2]));

        $this->assertCount(5, $pageOne);
        $this->assertCount(5, $pageTwo);
        $this->assertSame([], array_intersect(array_column($pageOne, 'id'), array_column($pageTwo, 'id')));
        $this->assertIsSortedAscending([...$this->getFirstSortValues($pageOne), ...$this->getFirstSortValues($pageTwo)]);
    }

    public function testSortOrderIsKeptByElementHydration(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => self::matchAll(), 'sort' => [['name.keyword' => 'desc']]],
            self::PLANT_PARAMETERS,
            hydrate: true
        );

        $elements = $this->getHydratedElements($response);
        $this->assertGreaterThan(3, count($elements));
        $names = array_map(static fn (array $element): string => $element['data']['name'], $elements);
        $this->assertIsSortedAscending(array_reverse($names));
        foreach ($elements as $element) {
            $this->assertCount(1, $element['metadata']['sort']);
            $this->assertIsFloat($element['metadata']['score']);
        }
    }

    public function testSortCanBeCombinedWithHighlight(): void
    {
        $response = $this->runElasticsearchStep(
            [
                'query' => ['match' => ['description' => 'lily']],
                'highlight' => ['fields' => ['description' => []]],
                'sort' => [['name.keyword' => 'asc']],
            ],
            self::PLANT_PARAMETERS
        );

        $elements = $this->getSearchElements($response);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            $this->assertArrayHasKey('highlight', $element['metadata']);
            $this->assertArrayHasKey('sort', $element['metadata']);
        }
    }

    public function testSortCanBeParameterizedWithExpressions(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => self::matchAll(), 'sort' => [['name.keyword' => '{{ direction }}']]],
            [...self::PLANT_PARAMETERS, 'direction' => 'desc']
        );

        $values = $this->getFirstSortValues($this->getSearchElements($response));
        $this->assertIsSortedAscending(array_reverse($values));
    }

    public function testEmptySortIsIgnored(): void
    {
        $elements = $this->sortedSearch([]);

        $this->assertNotEmpty($elements);
        $this->assertArrayNotHasKey('sort', $elements[0]['metadata']);
    }

    public function testSortingByTextFieldWithoutKeywordFailsWithExplanation(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => self::matchAll(), 'sort' => [['name' => 'asc']]],
            self::PLANT_PARAMETERS
        );

        $this->assertIsProblemResponse($response, 400);
        $this->assertSame('elasticsearch', $this->getBody($response)['failedStep']['source']);
    }

    /**
     * @return array<string, array{mixed, string}>
     */
    public static function forbiddenSortProvider(): array
    {
        return [
            'internal id' => [['_id'], "The field name '_id'"],
            'internal groups' => [[['_groupsWithSearchAccess.keyword' => 'asc']], "The field name '_groupsWithSearchAccess.keyword'"],
            'internal users' => [[['_usersWithSearchAccess.keyword' => 'asc']], "The field name '_usersWithSearchAccess.keyword'"],
            'internal field as string' => ['_index', "The field name '_index'"],
            'wildcard only' => [[['*' => 'asc']], "The field name '*'"],
            'wildcard prefix' => [[['*id' => 'asc']], "The field name '*id'"],
            'script sort' => [[['_script' => ['type' => 'number', 'script' => ['source' => '1'], 'order' => 'asc']]], 'scripts are not supported'],
            'geo distance sort' => [[['_geo_distance' => ['location' => [0, 0]]]], "The field name '_geo_distance'"],
            'nested sort' => [[['name' => ['nested' => ['path' => 'x']]]], "'nested'"],
            'unknown option' => [[['name.keyword' => ['order' => 'asc', 'foo' => 'bar']]], "Unsupported sort option 'foo'"],
            'unknown order' => [[['name.keyword' => 'up']], 'query.sort[0].name.keyword'],
            'unknown mode' => [[['name.keyword' => ['mode' => 'first']]], 'query.sort[0].name.keyword.mode'],
            'number' => [5, 'query.sort'],
            'item with multiple fields' => [[['name.keyword' => 'asc', 'scientificName.keyword' => 'asc']], 'query.sort[0]'],
            'nested list item' => [[['name.keyword']], 'query.sort[0]'],
        ];
    }

    #[DataProvider('forbiddenSortProvider')]
    public function testForbiddenSortsAreRejected(mixed $sort, string $expectedDetailPart): void
    {
        $response = $this->runElasticsearchStep(['query' => self::matchAll(), 'sort' => $sort], self::PLANT_PARAMETERS);

        $this->assertSearchStepIsRejected($response, $expectedDetailPart);
    }
}
