<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * The extended query definition `{query, highlight}` of the Elasticsearch step. Highlights are returned in the
 * metadata of the elements, but must never be able to reach internal fields.
 */
class SearchHighlightTest extends BaseSearchTestCase
{
    private const array LILY_QUERY = ['match' => ['description' => 'lily']];

    /**
     * @param array<string, mixed> $highlight
     */
    private function runHighlightSearch(array $highlight, bool $hydrate = false): ResponseInterface
    {
        return $this->runElasticsearchStep(
            ['query' => self::LILY_QUERY, 'highlight' => $highlight],
            ['nodeTypes' => ['Plant'], 'pageSize' => 5],
            $hydrate
        );
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function forbiddenHighlightFieldProvider(): array
    {
        return [
            'internal id' => [['_id' => []]],
            'internal groups with search access' => [['_groupsWithSearchAccess' => []]],
            'internal groups with search access, keyword' => [['_groupsWithSearchAccess.keyword' => []]],
            'internal users with search access' => [['_usersWithSearchAccess' => []]],
            'wildcard only' => [['*' => []]],
            'wildcard prefix' => [['*id' => []]],
            'single character wildcard prefix' => [['?id' => []]],
            'wildcard prefix to internal field' => [['*SearchAccess' => []]],
        ];
    }

    /**
     * @param array<string, mixed> $fields
     */
    #[DataProvider('forbiddenHighlightFieldProvider')]
    public function testHighlightOfInternalOrWildcardFieldsIsRejected(array $fields): void
    {
        $response = $this->runHighlightSearch(['fields' => $fields]);

        $this->assertSearchStepIsRejected($response, 'is not allowed');
        $this->assertStringContainsString('query.highlight.fields', $this->getBody($response)['detail']);
    }

    public function testHighlightOfInternalFieldInListNotationIsRejected(): void
    {
        $response = $this->runHighlightSearch(['fields' => [['_id' => []]]]);

        $this->assertSearchStepIsRejected($response, "The field name '_id'");
    }

    public function testHighlightWhichMatchesInternalFieldsIsRejected(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['description' => ['matched_fields' => ['_id']]]]);

        $this->assertSearchStepIsRejected($response, 'matched_fields[0]');
    }

    public function testCustomHighlightQueryChangesWhatIsHighlighted(): void
    {
        // the elements are found by "lily", but only "fire" is highlighted
        $response = $this->runHighlightSearch(['highlight_query' => ['match' => ['description' => 'fire']], 'fields' => ['description' => []]]);

        $fragments = [];
        foreach ($this->getSearchElements($response) as $element) {
            foreach ($element['metadata']['highlight']['description'] ?? [] as $fragment) {
                $fragments[] = $fragment;
            }
        }
        $this->assertNotEmpty($fragments);
        $this->assertStringContainsString('<em>fire</em>', implode(' ', $fragments));
        $this->assertDoesNotMatchRegularExpression('/<em>lily<\/em>/i', implode(' ', $fragments));
    }

    public function testCustomHighlightQueryOfSingleFieldIsAllowed(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['description' => ['highlight_query' => ['match' => ['description' => 'fire']]]]]);

        $this->assertNotEmpty($this->getSearchElements($response));
    }

    public function testHighlightQueryDoesNotChangeWhichElementsAreFound(): void
    {
        $withoutHighlightQuery = $this->getSearchElements($this->runHighlightSearch(['fields' => ['description' => []]]));
        $withHighlightQuery = $this->getSearchElements($this->runHighlightSearch(['highlight_query' => ['match' => ['description' => 'doesnotexistanywhere']], 'fields' => ['description' => []]]));

        $this->assertSame(array_column($withoutHighlightQuery, 'id'), array_column($withHighlightQuery, 'id'));
    }

    public function testForbiddenFeaturesInsideOfHighlightQueriesAreRejected(): void
    {
        $response = $this->runHighlightSearch(['highlight_query' => ['script_score' => ['query' => ['exists' => ['field' => 'name']], 'script' => ['source' => '1']]], 'fields' => ['description' => []]]);

        $this->assertSearchStepIsRejected($response, 'query.highlight.highlight_query.script_score');

        $response = $this->runHighlightSearch(['fields' => ['description' => ['highlight_query' => ['terms' => ['name' => ['index' => 'node_user', 'id' => 'x', 'path' => 'name']]]]]]);

        $this->assertSearchStepIsRejected($response, 'terms lookup');
    }

    public function testHighlightWithScriptIsRejected(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['description' => ['script' => 'x']]]);

        $this->assertSearchStepIsRejected($response, 'scripts are not supported');
    }

    public function testHighlightWithoutFieldsIsRejected(): void
    {
        $response = $this->runHighlightSearch(['pre_tags' => ['<b>']]);

        $this->assertSearchStepIsRejected($response, "require the property 'fields'");
    }

    public function testHighlightWhichIsNotAnObjectIsRejected(): void
    {
        $response = $this->runElasticsearchStep(['query' => self::LILY_QUERY, 'highlight' => 'description']);

        $this->assertSearchStepIsRejected($response, 'query.highlight');
    }

    public function testUnknownPropertyOfExtendedQueryDefinitionIsRejected(): void
    {
        $response = $this->runElasticsearchStep(['query' => self::LILY_QUERY, 'aggs' => ['x' => ['terms' => ['field' => 'name']]]]);

        $this->assertSearchStepIsRejected($response, "Unknown property 'aggs'");
    }

    public function testQueryOfExtendedQueryDefinitionMustBeAnObject(): void
    {
        $response = $this->runElasticsearchStep(['query' => 'lily']);

        $this->assertSearchStepIsRejected($response, 'query.query');
    }

    public function testHighlightIsReturnedInTheMetadataOfElements(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['description' => []]]);

        $elements = $this->getSearchElements($response);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            $this->assertIsNumeric($element['metadata']['score']);
            $this->assertArrayHasKey('highlight', $element['metadata']);
            $this->assertArrayHasKey('description', $element['metadata']['highlight']);
            $this->assertNotEmpty($element['metadata']['highlight']['description']);
            $this->assertStringContainsString('<em>', $element['metadata']['highlight']['description'][0]);
            $this->assertArrayNotHasKey('sort', $element['metadata']);
        }
    }

    public function testHighlightOptionsAreForwarded(): void
    {
        $response = $this->runHighlightSearch([
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>'],
            'fields' => ['description' => ['number_of_fragments' => 1]],
        ]);

        foreach ($this->getSearchElements($response) as $element) {
            $fragments = $element['metadata']['highlight']['description'];
            $this->assertCount(1, $fragments);
            $this->assertStringContainsString('<mark>', $fragments[0]);
            $this->assertStringNotContainsString('<em>', $fragments[0]);
        }
    }

    public function testHighlightFieldsInListNotationAreSupported(): void
    {
        $response = $this->runHighlightSearch(['fields' => [['description' => []]]]);

        foreach ($this->getSearchElements($response) as $element) {
            $this->assertArrayHasKey('description', $element['metadata']['highlight']);
        }
    }

    public function testWildcardSuffixInHighlightFieldIsAllowed(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['desc*' => []]]);

        foreach ($this->getSearchElements($response) as $element) {
            $this->assertArrayHasKey('description', $element['metadata']['highlight']);
        }
    }

    public function testShorthandQueryStillWorksAndHasNoHighlight(): void
    {
        $response = $this->runElasticsearchStep(self::LILY_QUERY, ['nodeTypes' => ['Plant'], 'pageSize' => 5]);

        $elements = $this->getSearchElements($response);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            $this->assertIsNumeric($element['metadata']['score']);
            $this->assertArrayNotHasKey('highlight', $element['metadata']);
        }
    }

    public function testExtendedQueryWithoutHighlightIsEquivalentToShorthand(): void
    {
        $parameters = ['nodeTypes' => ['Plant'], 'pageSize' => 5];

        $shorthand = $this->getSearchElements($this->runElasticsearchStep(self::LILY_QUERY, $parameters));
        $extended = $this->getSearchElements($this->runElasticsearchStep(['query' => self::LILY_QUERY], $parameters));

        $this->assertSame($shorthand, $extended);
    }

    public function testExpressionsAreResolvedInsideHighlightOptions(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => self::LILY_QUERY, 'highlight' => ['pre_tags' => ['{{ tag }}'], 'post_tags' => ['</span>'], 'fields' => ['description' => []]]],
            ['nodeTypes' => ['Plant'], 'pageSize' => 1, 'tag' => '<span>']
        );

        $elements = $this->getSearchElements($response);
        $this->assertNotEmpty($elements);
        $this->assertStringContainsString('<span>', $elements[0]['metadata']['highlight']['description'][0]);
    }

    public function testExpressionsCanNotSmuggleInInternalHighlightFields(): void
    {
        $response = $this->runElasticsearchStep(
            ['query' => self::LILY_QUERY, 'highlight' => ['fields' => '{{ fields }}']],
            ['nodeTypes' => ['Plant'], 'fields' => ['_groupsWithSearchAccess' => []]]
        );

        $this->assertSearchStepIsRejected($response, "The field name '_groupsWithSearchAccess'");
    }

    public function testHighlightsAreReturnedByElementHydration(): void
    {
        $response = $this->runHighlightSearch(['fields' => ['description' => []]], hydrate: true);

        $elements = $this->getHydratedElements($response);
        $this->assertNotEmpty($elements);
        foreach ($elements as $element) {
            $keys = array_keys($element);
            $this->assertSame('metadata', end($keys), 'metadata is expected after the native properties');
            $this->assertArrayHasKey('highlight', $element['metadata']);
        }
    }
}
