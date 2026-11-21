<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

/**
 * Metadata of search steps (e.g. Elasticsearch's score and highlights) is associated with the element ids, survives
 * steps which do not know about it, and is returned by the element hydration step in `metadata`, after the native properties of the element.
 */
class SearchMetadataTest extends BaseSearchTestCase
{
    private const array ES_PARAMETERS = ['nodeTypes' => ['Plant'], 'pageSize' => 3];

    /**
     * @param array<string, mixed> $query
     *
     * @return array<string, mixed>
     */
    private function elasticsearchStep(array $query): array
    {
        return ['type' => 'elasticsearch-query-dsl-mixin', 'query' => $query, 'parameters' => self::ES_PARAMETERS];
    }

    /**
     * @param array<string, mixed> $element
     */
    private function assertMetadataIsPlacedLast(array $element): void
    {
        $keys = array_keys($element);
        $this->assertSame('metadata', end($keys));
        $this->assertLessThan(array_search('metadata', $keys, true), array_search('data', $keys, true));
    }

    public function testHydrationReturnsMetadataOfElasticsearchStep(): void
    {
        $response = $this->runElasticsearchStep(['match' => ['description' => 'lily']], self::ES_PARAMETERS, hydrate: true);

        $searchIds = array_column($this->getSearchElements($this->runElasticsearchStep(['match' => ['description' => 'lily']], self::ES_PARAMETERS)), 'id');
        $elements = $this->getHydratedElements($response);
        $this->assertSame($searchIds, array_column($elements, 'id'));
        foreach ($elements as $element) {
            $this->assertMetadataIsPlacedLast($element);
            $this->assertSame(['score'], array_keys($element['metadata']));
            $this->assertIsFloat($element['metadata']['score']);
        }
    }

    public function testMetadataIsAFlatObject(): void
    {
        $response = $this->runElasticsearchStep(['match' => ['description' => 'lily']], self::ES_PARAMETERS, hydrate: true);

        $this->assertMatchesRegularExpression('/"metadata":\{"score":[0-9.]+\}/', (string) $response->getBody());
    }

    public function testHydrationWithoutMetadataDoesNotAddMetadata(): void
    {
        $response = $this->runSearch([
            ['type' => 'element-hydration', 'query' => ['elementIds' => ['258c0dfe-b1d8-4839-beed-d00d1b544a96']]],
        ]);

        $elements = $this->getHydratedElements($response);
        $this->assertCount(1, $elements);
        $this->assertArrayNotHasKey('metadata', $elements[0]);
    }

    public function testMetadataOfMultipleStepsIsMergedAndLaterStepsOverwriteEarlierOnes(): void
    {
        $firstQuery = ['match' => ['description' => 'lily']];
        $secondQuery = ['query' => ['match' => ['name' => 'lily']], 'highlight' => ['fields' => ['name' => []]]];

        $scoresOfFirstStep = $this->getScoresById($this->runElasticsearchStep($firstQuery, self::ES_PARAMETERS));
        $scoresOfSecondStep = $this->getScoresById($this->runElasticsearchStep($secondQuery, self::ES_PARAMETERS));
        $response = $this->runSearch([
            $this->elasticsearchStep($firstQuery),
            $this->elasticsearchStep($secondQuery),
            ['type' => 'element-hydration'],
        ]);

        $elements = $this->getHydratedElements($response);
        $this->assertNotEmpty($elements);
        $scoreWasOverwritten = false;
        foreach ($elements as $element) {
            $this->assertMetadataIsPlacedLast($element);
            $this->assertFalse(array_is_list($element['metadata']));
            $this->assertArrayHasKey($element['id'], $scoresOfSecondStep);
            $this->assertSame($scoresOfSecondStep[$element['id']], $element['metadata']['score'], 'The score of the later step is expected.');
            $this->assertArrayHasKey('highlight', $element['metadata']);
            if (array_key_exists($element['id'], $scoresOfFirstStep) && $scoresOfFirstStep[$element['id']] !== $scoresOfSecondStep[$element['id']]) {
                $scoreWasOverwritten = true;
            }
        }
        $this->assertTrue($scoreWasOverwritten, 'Expected at least one element found by both steps with different scores.');
    }

    /**
     * @return array<string, float>
     */
    private function getScoresById(\Psr\Http\Message\ResponseInterface $response): array
    {
        $scores = [];
        foreach ($this->getSearchElements($response) as $element) {
            $scores[$element['id']] = $element['metadata']['score'];
        }

        return $scores;
    }

    public function testMetadataSurvivesStepsWhichDoNotKnowAboutIt(): void
    {
        $response = $this->runSearch([
            $this->elasticsearchStep(['query' => ['match' => ['name' => 'lily']], 'highlight' => ['fields' => ['name' => []]]]),
            [
                'type' => 'cypher-path-subset',
                'query' => 'UNWIND $stepResults[0].elements AS element MATCH path=((plant:Plant)-[:IS_MEMBER_OF*..]->(:Taxon)) WHERE plant.id = element.id RETURN path',
            ],
            ['type' => 'element-hydration'],
        ]);

        $elements = $this->getHydratedElements($response);
        $withMetadata = array_values(array_filter($elements, static fn (array $element): bool => array_key_exists('metadata', $element)));
        $withoutMetadata = array_filter($elements, static fn (array $element): bool => !array_key_exists('metadata', $element));

        $this->assertNotEmpty($withMetadata, 'Metadata of the Elasticsearch step must be available to the hydration after the Cypher step.');
        $this->assertNotEmpty($withoutMetadata, 'Taxa and relations of the paths were not found by Elasticsearch and must not have metadata.');
        foreach ($withMetadata as $element) {
            $this->assertSame('Plant', $element['type']);
            $this->assertArrayHasKey('highlight', $element['metadata']);
        }
    }

    public function testExplicitElementIdsAreEnrichedWithMetadataOfPreviousSteps(): void
    {
        $response = $this->runSearch([
            $this->elasticsearchStep(['match' => ['name' => 'flame lily']]),
            ['type' => 'element-hydration', 'query' => ['elementIds' => ['258c0dfe-b1d8-4839-beed-d00d1b544a96']]],
        ]);

        $elements = $this->getHydratedElements($response);
        $this->assertCount(1, $elements);
        $this->assertSame('258c0dfe-b1d8-4839-beed-d00d1b544a96', $elements[0]['id']);
        $this->assertIsFloat($elements[0]['metadata']['score']);
    }

    public function testHighlightSortScoreAndMinScoreCanBeCombined(): void
    {
        $response = $this->runElasticsearchStep(
            [
                'query' => ['match' => ['description' => 'lily']],
                'highlight' => ['fields' => ['description' => []]],
                'sort' => [['name.keyword' => 'desc']],
            ],
            ['nodeTypes' => ['Plant'], 'pageSize' => 3, 'minScore' => 0.1],
            hydrate: true
        );

        $elements = $this->getHydratedElements($response);
        $this->assertNotEmpty($elements);
        $names = [];
        foreach ($elements as $element) {
            $metadata = $element['metadata'];
            $this->assertIsNumeric($metadata['score']);
            $this->assertGreaterThanOrEqual(0.1, $metadata['score']);
            $this->assertNotEmpty($metadata['highlight']['description']);
            $this->assertSame([$element['data']['name']], $metadata['sort']);
            $names[] = $element['data']['name'];
        }
        $sortedNames = $names;
        rsort($sortedNames, SORT_STRING);
        $this->assertSame($sortedNames, $names);
    }
}
