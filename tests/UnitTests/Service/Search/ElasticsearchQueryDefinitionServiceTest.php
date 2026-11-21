<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Search;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\Search\ElasticsearchForbiddenFeatureCheckService;
use App\Service\Search\ElasticsearchQueryDefinitionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(ElasticsearchQueryDefinitionService::class)]
class ElasticsearchQueryDefinitionServiceTest extends TestCase
{
    private const array QUERY = ['match' => ['name' => 'lily']];

    private function buildService(): ElasticsearchQueryDefinitionService
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://mock.dev/error');
        $factory = new Client400BadContentExceptionFactory($urlGenerator);

        return new ElasticsearchQueryDefinitionService(new ElasticsearchForbiddenFeatureCheckService($factory), $factory);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function assertIsRejected(array $input, string $expectedDetailPart): void
    {
        try {
            $this->buildService()->parse($input);
            $this->fail('Expected the input to be rejected.');
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString($expectedDetailPart, $exception->getDetail());
        }
    }

    public function testShorthandQueryIsReturnedAsIs(): void
    {
        $definition = $this->buildService()->parse(self::QUERY);

        $this->assertSame(self::QUERY, $definition->query);
        $this->assertNull($definition->highlight);
        $this->assertNull($definition->sort);
    }

    public function testShorthandQueryIsCheckedForForbiddenFeatures(): void
    {
        $this->assertIsRejected(['script_score' => []], "at 'query.script_score'");
    }

    public function testExtendedDefinitionWithOnlyQuery(): void
    {
        $definition = $this->buildService()->parse(['query' => self::QUERY]);

        $this->assertSame(self::QUERY, $definition->query);
        $this->assertNull($definition->highlight);
        $this->assertNull($definition->sort);
    }

    public function testExtendedDefinitionIsCheckedForForbiddenFeatures(): void
    {
        $this->assertIsRejected(['query' => ['script_score' => []]], "at 'query.query.script_score'");
    }

    public function testExtendedDefinitionRejectsUnknownProperties(): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'aggs' => []], "Unknown property 'aggs'");
        $this->assertIsRejected(['query' => self::QUERY, 'from' => 5], "Unknown property 'from'");
        $this->assertIsRejected(['query' => self::QUERY, 'size' => 5], "Unknown property 'size'");
    }

    public function testExtendedDefinitionRequiresQueryToBeAnObject(): void
    {
        $this->assertIsRejected(['query' => 'lily'], 'query.query');
        $this->assertIsRejected(['query' => null], 'query.query');
    }

    public function testHighlightIsNormalized(): void
    {
        $definition = $this->buildService()->parse([
            'query' => self::QUERY,
            'highlight' => ['pre_tags' => ['<b>'], 'fields' => ['name' => [], 'description' => ['number_of_fragments' => 1]]],
        ]);

        $this->assertNotNull($definition->highlight);
        $this->assertSame(['<b>'], $definition->highlight['pre_tags']);
        $this->assertEquals(['name' => new stdClass(), 'description' => ['number_of_fragments' => 1]], $definition->highlight['fields']);
        $this->assertInstanceOf(stdClass::class, $definition->highlight['fields']['name'], 'Empty field options need to be objects for Elasticsearch.');
    }

    public function testHighlightFieldsInListNotationAreKept(): void
    {
        $definition = $this->buildService()->parse([
            'query' => self::QUERY,
            'highlight' => ['fields' => [['name' => []], ['description' => ['fragment_size' => 10]]]],
        ]);

        $this->assertNotNull($definition->highlight);
        $this->assertEquals([['name' => new stdClass()], ['description' => ['fragment_size' => 10]]], $definition->highlight['fields']);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function forbiddenFieldNameProvider(): array
    {
        return [
            ['_id'],
            ['_groupsWithSearchAccess'],
            ['_groupsWithSearchAccess.keyword'],
            ['_usersWithSearchAccess'],
            ['*'],
            ['*id'],
            ['?id'],
            ['?'],
            ['_*'],
        ];
    }

    #[DataProvider('forbiddenFieldNameProvider')]
    public function testHighlightRejectsInternalAndWildcardPrefixedFields(string $fieldName): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => [$fieldName => []]]], sprintf("The field name '%s'", $fieldName));
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => [[$fieldName => []]]]], sprintf("The field name '%s'", $fieldName));
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => ['name' => ['matched_fields' => [$fieldName]]]]], sprintf("The field name '%s'", $fieldName));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function allowedFieldNameProvider(): array
    {
        return [['name'], ['name.keyword'], ['desc*'], ['na?e'], ['a_b'], ['n*'], ['images.description']];
    }

    #[DataProvider('allowedFieldNameProvider')]
    public function testHighlightAllowsRegularFieldsAndWildcardSuffixes(string $fieldName): void
    {
        $definition = $this->buildService()->parse(['query' => self::QUERY, 'highlight' => ['fields' => [$fieldName => []]]]);

        $this->assertNotNull($definition->highlight);
        $this->assertArrayHasKey($fieldName, $definition->highlight['fields']);
    }

    public function testHighlightValidation(): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => 'name'], 'query.highlight');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['name']], 'query.highlight');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => []], "require the property 'fields'");
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['pre_tags' => ['x']]], "require the property 'fields'");
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => []]], 'query.highlight.fields');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => 'name']], 'query.highlight.fields');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => ['name' => 'x']]], 'query.highlight.fields.name');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => [['a' => [], 'b' => []]]]], 'query.highlight.fields[0]');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => [['name']]]], 'query.highlight.fields[0]');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => ['name' => ['matched_fields' => 'x']]]], 'matched_fields');
    }

    public function testHighlightRejectsForbiddenFeatures(): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['highlight_query' => ['script_score' => []], 'fields' => ['name' => []]]], "at 'query.highlight.highlight_query.script_score'");
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => ['name' => ['highlight_query' => ['wrapper' => []]]]]], 'wrapper');
        $this->assertIsRejected(['query' => self::QUERY, 'highlight' => ['fields' => ['name' => ['script' => 'x']]]], 'scripts are not supported');
    }

    public function testHighlightQueriesAreAllowed(): void
    {
        $highlightQuery = ['match' => ['name' => 'fire']];

        $definition = $this->buildService()->parse([
            'query' => self::QUERY,
            'highlight' => ['highlight_query' => $highlightQuery, 'fields' => ['name' => ['highlight_query' => $highlightQuery]]],
        ]);

        $this->assertNotNull($definition->highlight);
        $this->assertSame($highlightQuery, $definition->highlight['highlight_query']);
        $this->assertSame($highlightQuery, $definition->highlight['fields']['name']['highlight_query']);
    }

    public function testSortIsNormalizedToAList(): void
    {
        $service = $this->buildService();

        $this->assertSame(['name.keyword'], $service->parse(['query' => self::QUERY, 'sort' => 'name.keyword'])->sort);
        $this->assertSame([['name.keyword' => 'asc']], $service->parse(['query' => self::QUERY, 'sort' => ['name.keyword' => 'asc']])->sort);
        $this->assertSame([['name.keyword' => 'asc']], $service->parse(['query' => self::QUERY, 'sort' => [['name.keyword' => 'asc']]])->sort);
        $this->assertSame(['_score', ['name.keyword' => 'desc']], $service->parse(['query' => self::QUERY, 'sort' => ['_score', ['name.keyword' => 'desc']]])->sort);
        $this->assertSame([['_score' => 'asc']], $service->parse(['query' => self::QUERY, 'sort' => [['_score' => 'asc']]])->sort);
    }

    public function testSortOptionsAreKept(): void
    {
        $sort = [['name.keyword' => ['order' => 'desc', 'missing' => '_last', 'mode' => 'min', 'unmapped_type' => 'keyword']]];

        $this->assertSame($sort, $this->buildService()->parse(['query' => self::QUERY, 'sort' => $sort])->sort);
    }

    public function testEmptySortIsIgnored(): void
    {
        $this->assertNull($this->buildService()->parse(['query' => self::QUERY, 'sort' => []])->sort);
    }

    #[DataProvider('forbiddenFieldNameProvider')]
    public function testSortRejectsInternalAndWildcardPrefixedFields(string $fieldName): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => $fieldName], sprintf("The field name '%s'", $fieldName));
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [[$fieldName => 'asc']]], sprintf("The field name '%s'", $fieldName));
    }

    public function testSortValidation(): void
    {
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => 5], 'query.sort');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [5]], 'query.sort[0]');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => 'asc', 'b' => 'asc']]], 'query.sort[0]');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => 'up']]], 'query.sort[0].a');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => 5]]], 'query.sort[0].a');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => ['order' => 'up']]]], 'query.sort[0].a.order');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => ['mode' => 'first']]]], 'query.sort[0].a.mode');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => ['foo' => 'bar']]]], "Unsupported sort option 'foo'");
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['a' => ['nested' => ['path' => 'x']]]]], "'nested'");
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['_script' => ['script' => 'x']]]], 'scripts are not supported');
        $this->assertIsRejected(['query' => self::QUERY, 'sort' => [['_geo_distance' => ['loc' => [0, 0]]]]], "The field name '_geo_distance'");
    }
}
