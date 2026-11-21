<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Elasticsearch features which could be used to look at data beyond the elements users have search access to, or to
 * run code, are rejected, regardless of how deeply they are nested or whether they are provided by expressions.
 */
class SearchForbiddenFeatureTest extends BaseSearchTestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function forbiddenQueryProvider(): array
    {
        $matchAll = self::matchAll();

        return [
            'script query' => [['bool' => ['filter' => [['script' => ['script' => ['source' => 'true']]]]]], 'scripts are not supported'],
            'script score' => [['script_score' => ['query' => $matchAll, 'script' => ['source' => '1']]], "'script_score'"],
            'function score with script score' => [['function_score' => ['query' => $matchAll, 'functions' => [['script_score' => ['script' => ['source' => '1']]]]]], 'scripts are not supported'],
            'minimum should match script' => [['terms_set' => ['name' => ['terms' => ['a'], 'minimum_should_match_script' => ['source' => '1']]]], 'scripts are not supported'],
            'terms lookup' => [['terms' => ['name' => ['index' => 'node_user', 'id' => 'x', 'path' => 'name']]], 'terms lookup'],
            'terms lookup in bool' => [['bool' => ['must' => [['terms' => ['name' => ['index' => 'node_user', 'id' => 'x', 'path' => 'name']]]]]], 'terms lookup'],
            'more like this with document reference' => [['more_like_this' => ['fields' => ['name'], 'like' => [['_index' => 'node_user', '_id' => 'x']], 'min_term_freq' => 1]], 'more_like_this document reference'],
            'more like this with artificial document' => [['more_like_this' => ['fields' => ['name'], 'like' => [['doc' => ['name' => 'x']]], 'min_term_freq' => 1]], 'more_like_this document reference'],
            'more like this with ids' => [['more_like_this' => ['fields' => ['name'], 'like' => 'text', 'ids' => ['x']]], "'ids'"],
            'geo shape with indexed shape' => [['geo_shape' => ['location' => ['indexed_shape' => ['index' => 'shapes', 'id' => 'x', 'path' => 'shape']]]], 'indexed_shape'],
            'percolate' => [['percolate' => ['field' => 'query', 'index' => 'node_user', 'id' => 'x']], 'percolate'],
            'wrapper' => [['wrapper' => ['query' => 'e30=']], 'wrapper'],
            'has child' => [['has_child' => ['type' => 'x', 'query' => $matchAll]], 'joining queries are not supported'],
            'has parent' => [['has_parent' => ['parent_type' => 'x', 'query' => $matchAll]], 'joining queries are not supported'],
            'parent id' => [['parent_id' => ['type' => 'x', 'id' => 'y']], 'joining queries are not supported'],
            'nested' => [['nested' => ['path' => 'images', 'query' => $matchAll]], 'joining queries are not supported'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('forbiddenQueryProvider')]
    public function testForbiddenFeaturesAreRejectedInShorthandQueries(array $query, string $expectedDetailPart): void
    {
        $response = $this->runElasticsearchStep($query, ['nodeTypes' => ['Plant']]);

        $this->assertSearchStepIsRejected($response, $expectedDetailPart);
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('forbiddenQueryProvider')]
    public function testForbiddenFeaturesAreRejectedInExtendedQueries(array $query, string $expectedDetailPart): void
    {
        $response = $this->runElasticsearchStep(['query' => $query], ['nodeTypes' => ['Plant']]);

        $this->assertSearchStepIsRejected($response, $expectedDetailPart);
        $this->assertStringContainsString('query.query', $this->getBody($response)['detail']);
    }

    public function testForbiddenFeaturesProvidedByExpressionsAreRejected(): void
    {
        $response = $this->runElasticsearchStep(
            ['terms' => ['name' => '{{ lookup }}']],
            ['nodeTypes' => ['Plant'], 'lookup' => ['index' => 'node_user', 'id' => 'x', 'path' => 'name']]
        );

        $this->assertSearchStepIsRejected($response, 'terms lookup');

        $response = $this->runElasticsearchStep(
            ['query' => '{{ query }}'],
            ['nodeTypes' => ['Plant'], 'query' => ['script_score' => ['query' => ['match_all' => []], 'script' => ['source' => '1']]]]
        );

        $this->assertSearchStepIsRejected($response, 'scripts are not supported');
    }

    public function testRejectionExplainsTheLocationOfTheForbiddenFeature(): void
    {
        $response = $this->runElasticsearchStep(
            ['bool' => ['must' => [['match_all' => []], ['wrapper' => ['query' => 'e30=']]]]],
            ['nodeTypes' => ['Plant']]
        );

        $this->assertSearchStepIsRejected($response, 'query.bool.must[1].wrapper');
    }

    public function testRejectedQueriesAreNotSentToElasticsearch(): void
    {
        $response = $this->runElasticsearchStep(['wrapper' => ['query' => 'e30=']], ['nodeTypes' => ['Plant']]);

        // a failure of Elasticsearch would be explained by the database, this one is explained by the validation
        $this->assertIsProblemResponse($response, 400);
        $this->assertArrayNotHasKey('source', $this->getBody($response)['failedStep']);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function allowedQueryProvider(): array
    {
        return [
            'match on description (contains the letters of script)' => [['match' => ['description' => 'lily']]],
            'terms with a list of values' => [['terms' => ['name.keyword' => ['Lily', 'Tulip']]]],
            'more like this with texts' => [['more_like_this' => ['fields' => ['description'], 'like' => ['lily', 'tulip'], 'min_term_freq' => 1, 'min_doc_freq' => 1]]],
            'bool combination' => [['bool' => ['must' => [['match' => ['name' => 'lily']]], 'filter' => [['exists' => ['field' => 'name']]]]]],
            'query string' => [['query_string' => ['query' => 'name:lily']]],
            'range' => [['range' => ['created' => ['gte' => '2000-01-01']]]],
            'prefix' => [['prefix' => ['name.keyword' => ['value' => 'Li']]]],
            'function score without script' => [['function_score' => ['query' => self::matchAll(), 'boost' => 2]]],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('allowedQueryProvider')]
    public function testAllowedQueriesStillWork(array $query): void
    {
        $response = $this->runElasticsearchStep($query, ['nodeTypes' => ['Plant']]);

        $this->assertIsSearchResultResponse($response);
    }
}
