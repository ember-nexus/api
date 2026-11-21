<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Search;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\Search\ElasticsearchForbiddenFeatureCheckService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(ElasticsearchForbiddenFeatureCheckService::class)]
class ElasticsearchForbiddenFeatureCheckServiceTest extends TestCase
{
    private function buildService(): ElasticsearchForbiddenFeatureCheckService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://mock.dev/error');

        return new ElasticsearchForbiddenFeatureCheckService(new Client400BadContentExceptionFactory($urlGenerator));
    }

    /**
     * @return array<string, array{array<string, mixed>, string, string}> query, expected location, expected reason
     */
    public static function forbiddenQueryProvider(): array
    {
        return [
            'script' => [['bool' => ['filter' => ['script' => ['script' => 'x']]]], 'q.bool.filter.script', 'scripts are not supported'],
            'script score' => [['script_score' => ['query' => [], 'script' => 'x']], 'q.script_score', 'scripts are not supported'],
            'script fields' => [['script_fields' => []], 'q.script_fields', 'scripts are not supported'],
            'script sort' => [['_script' => []], 'q._script', 'scripts are not supported'],
            'minimum should match script' => [['terms_set' => ['f' => ['minimum_should_match_script' => 'x']]], 'q.terms_set.f.minimum_should_match_script', 'scripts are not supported'],
            'script in different case' => [['Script' => []], 'q.Script', 'scripts are not supported'],
            'dotted script key' => [['a.script' => []], 'q.a.script', 'scripts are not supported'],
            'script in a list' => [['bool' => ['must' => [[], ['script' => []]]]], 'q.bool.must[1].script', 'scripts are not supported'],
            'wrapper' => [['wrapper' => ['query' => 'e30=']], 'q.wrapper', 'wrapper'],
            'indexed shape' => [['geo_shape' => ['f' => ['indexed_shape' => []]]], 'q.geo_shape.f.indexed_shape', 'other documents'],
            'percolate' => [['percolate' => []], 'q.percolate', 'other documents'],
            'has child' => [['has_child' => []], 'q.has_child', 'joining queries'],
            'has parent' => [['has_parent' => []], 'q.has_parent', 'joining queries'],
            'parent id' => [['parent_id' => []], 'q.parent_id', 'joining queries'],
            'nested' => [['nested' => []], 'q.nested', 'joining queries'],
            'script inside of a highlight query' => [['fields' => ['f' => ['highlight_query' => ['script' => ['script' => 'x']]]]], 'q.fields.f.highlight_query.script', 'scripts are not supported'],
            'terms lookup inside of a highlight query' => [['highlight_query' => ['terms' => ['f' => ['index' => 'i']]], 'fields' => []], 'q.highlight_query.terms.f', 'other documents'],
            'terms lookup' => [['terms' => ['f' => ['index' => 'i', 'id' => '1', 'path' => 'p']]], 'q.terms.f', 'other documents'],
            'terms lookup next to boost' => [['terms' => ['boost' => 1.5, 'f' => ['index' => 'i']]], 'q.terms.f', 'other documents'],
            'nested terms lookup' => [['bool' => ['should' => [['terms' => ['f' => ['id' => '1']]]]]], 'q.bool.should[0].terms.f', 'other documents'],
            'more like this document reference' => [['more_like_this' => ['like' => [['_id' => '1']]]], 'q.more_like_this.like[0]', 'documents are looked up'],
            'more like this artificial document' => [['more_like_this' => ['unlike' => [['doc' => []]]]], 'q.more_like_this.unlike[0]', 'documents are looked up'],
            'more like this like as object' => [['more_like_this' => ['like' => ['_id' => '1']]], 'q.more_like_this.like', 'documents are looked up'],
            'more like this docs' => [['more_like_this' => ['docs' => []]], 'q.more_like_this.docs', 'documents are looked up'],
            'more like this ids' => [['more_like_this' => ['ids' => ['1']]], 'q.more_like_this.ids', 'documents are looked up'],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('forbiddenQueryProvider')]
    public function testForbiddenFeaturesAreRejected(array $query, string $expectedLocation, string $expectedReason): void
    {
        try {
            $this->buildService()->assertNoForbiddenFeatures($query, 'q');
            $this->fail('Expected the query to be rejected.');
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString(sprintf("at '%s'", $expectedLocation), $exception->getDetail());
            $this->assertStringContainsString($expectedReason, $exception->getDetail());
        }
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function allowedQueryProvider(): array
    {
        return [
            'empty' => [[]],
            'description contains the letters of script' => [['match' => ['description' => ['query' => 'lily']]]],
            'subscript' => [['match' => ['subscript' => 'x']]],
            'terms with values' => [['terms' => ['f' => ['a', 'b'], 'boost' => 2]]],
            'terms with empty values' => [['terms' => ['f' => []]]],
            'terms set without script' => [['terms_set' => ['f' => ['terms' => ['a'], 'minimum_should_match_field' => 'n']]]],
            'more like this with texts' => [['more_like_this' => ['like' => ['a', 'b'], 'unlike' => 'c']]],
            'like outside of more like this' => [['match' => ['like' => ['query' => 'x']]]],
            'script as value' => [['match' => ['name' => 'script']]],
            'nested as value' => [['match' => ['name' => 'nested']]],
            'dotted nested field' => [['match' => ['nested.value' => 'x']]],
            'highlight query' => [['highlight_query' => ['match' => ['f' => 'x']], 'fields' => ['f' => ['highlight_query' => ['match' => ['f' => 'y']]]]]],
            'bool' => [['bool' => ['must' => [['match' => ['a' => 'b']]], 'filter' => [['range' => ['c' => ['gte' => 1]]]]]]],
        ];
    }

    /**
     * @param array<string, mixed> $query
     */
    #[DataProvider('allowedQueryProvider')]
    public function testAllowedQueriesAreAccepted(array $query): void
    {
        $this->buildService()->assertNoForbiddenFeatures($query, 'q');
        $this->addToAssertionCount(1);
    }
}
