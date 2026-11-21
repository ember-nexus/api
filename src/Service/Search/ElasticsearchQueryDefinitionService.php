<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\ElasticsearchQueryDefinition;
use stdClass;

/**
 * Parses and validates the (already expression-resolved) query input of the Elasticsearch query DSL mixin search step.
 *
 * Two forms are supported:
 *
 * - Shorthand: the input is the Elasticsearch query itself, e.g. `{"match": {...}}`.
 * - Extended: the input is an object with the property `query` (the Elasticsearch query) and optionally `highlight`
 *   (Elasticsearch highlight options) and `sort`. As Elasticsearch has no query clause named `query`, both forms can
 *   not be mixed up.
 *
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class ElasticsearchQueryDefinitionService
{
    private const array EXTENDED_DEFINITION_PROPERTIES = ['query', 'highlight', 'sort'];
    private const array SORT_ORDERS = ['asc', 'desc'];
    private const array SORT_OPTIONS = ['order', 'missing', 'mode', 'unmapped_type', 'numeric_type', 'format'];
    private const array SORT_MODES = ['min', 'max', 'sum', 'avg', 'median'];
    private const string SORT_BY_SCORE = '_score';

    public function __construct(
        private ElasticsearchForbiddenFeatureCheckService $forbiddenFeatureCheckService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     *
     * @throws Client400BadContentException
     */
    public function parse(array $input): ElasticsearchQueryDefinition
    {
        if (!array_key_exists('query', $input)) {
            $this->forbiddenFeatureCheckService->assertNoForbiddenFeatures($input, 'query');

            return new ElasticsearchQueryDefinition($input);
        }

        foreach (array_keys($input) as $property) {
            if (!in_array($property, self::EXTENDED_DEFINITION_PROPERTIES, true)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Unknown property '%s' in the extended query definition, supported properties are: %s.", $property, implode(', ', self::EXTENDED_DEFINITION_PROPERTIES)));
            }
        }

        $query = $input['query'];
        if (!is_array($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query.query', 'object', $query);
        }
        /** @var array<string, mixed> $query */
        $this->forbiddenFeatureCheckService->assertNoForbiddenFeatures($query, 'query.query');

        return new ElasticsearchQueryDefinition(
            $query,
            $this->parseHighlight($input),
            $this->parseSort($input)
        );
    }

    /**
     * Field names must not be able to reach internal fields (starting with `_`), neither directly nor by wildcard.
     * Therefore, the first character must be neither `_`, `*` nor `?`.
     */
    private function assertFieldNameIsAllowed(mixed $fieldName, string $path): void
    {
        if (!is_string($fieldName) || '' === $fieldName) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($path, 'non-empty string', $fieldName);
        }
        if (in_array($fieldName[0], ['_', '*', '?'], true)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("The field name '%s' at '%s' is not allowed: internal fields (starting with an underscore) as well as field names starting with a wildcard (* or ?) are not supported.", $fieldName, $path));
        }
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>|null
     */
    private function parseHighlight(array $input): ?array
    {
        if (!array_key_exists('highlight', $input)) {
            return null;
        }
        $highlight = $input['highlight'];
        if (!is_array($highlight) || (array_is_list($highlight) && count($highlight) > 0)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query.highlight', 'object', $highlight);
        }
        /** @var array<string, mixed> $highlight */
        $this->forbiddenFeatureCheckService->assertNoForbiddenFeatures($highlight, 'query.highlight');

        if (!array_key_exists('fields', $highlight)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("The highlight options require the property 'fields' to be set, e.g. {\"fields\": {\"name\": {}}}.");
        }
        $fields = $highlight['fields'];
        if (!is_array($fields) || 0 === count($fields)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query.highlight.fields', 'non-empty object', $fields);
        }

        // Elasticsearch accepts both {name: options} and [{name: options}, ...]
        $normalizedFields = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($fields as $key => $value) {
            if (is_int($key)) {
                if (!is_array($value) || 1 !== count($value)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('query.highlight.fields[%d]', $key), 'object with exactly one field', $value);
                }
                $normalizedFields[] = $this->parseHighlightField((string) array_key_first($value), reset($value), sprintf('query.highlight.fields[%d]', $key), true);
                continue;
            }
            $normalizedFields += $this->parseHighlightField($key, $value, 'query.highlight.fields', false);
        }
        $highlight['fields'] = $normalizedFields;

        return $highlight;
    }

    /**
     * @return array<string, mixed>
     */
    private function parseHighlightField(string $fieldName, mixed $options, string $path, bool $isListItem): array
    {
        $fieldPath = $isListItem ? $path : sprintf('%s.%s', $path, $fieldName);
        $this->assertFieldNameIsAllowed($fieldName, $fieldPath);
        if (!is_array($options)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($fieldPath, 'object', $options);
        }
        if (array_key_exists('matched_fields', $options)) {
            $matchedFields = $options['matched_fields'];
            if (!is_array($matchedFields)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('%s.matched_fields', $fieldPath), 'array', $matchedFields);
            }
            /** @psalm-suppress MixedAssignment */
            foreach ($matchedFields as $index => $matchedField) {
                $this->assertFieldNameIsAllowed($matchedField, sprintf('%s.matched_fields[%d]', $fieldPath, (int) $index));
            }
        }

        // PHP can not distinguish between empty arrays and empty objects, but Elasticsearch expects objects
        $normalizedOptions = 0 === count($options) ? new stdClass() : $options;

        return [$fieldName => $normalizedOptions];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return list<string|array<string, string|array<string, mixed>|stdClass>>|null
     */
    private function parseSort(array $input): ?array
    {
        if (!array_key_exists('sort', $input)) {
            return null;
        }
        /** @psalm-suppress MixedAssignment */
        $sort = $input['sort'];
        /** @psalm-suppress TypeDoesNotContainType */
        if (is_string($sort) || (is_array($sort) && !array_is_list($sort))) {
            $sort = [$sort];
        } elseif (!is_array($sort)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query.sort', 'string | object | array', $sort);
        }
        $this->forbiddenFeatureCheckService->assertNoForbiddenFeatures($sort, 'query.sort');

        $normalizedSort = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($sort as $index => $item) {
            $path = sprintf('query.sort[%d]', $index);
            if (is_string($item)) {
                $this->assertSortFieldIsAllowed($item, $path);
                $normalizedSort[] = $item;
                continue;
            }
            if (!is_array($item) || 1 !== count($item)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate($path, 'string | object with exactly one field', $item);
            }
            $fieldName = (string) array_key_first($item);
            $this->assertSortFieldIsAllowed($fieldName, $path);
            $normalizedSort[] = [$fieldName => $this->parseSortOptions($item[$fieldName], sprintf('%s.%s', $path, $fieldName))];
        }

        return 0 === count($normalizedSort) ? null : $normalizedSort;
    }

    private function assertSortFieldIsAllowed(string $fieldName, string $path): void
    {
        if (self::SORT_BY_SCORE === $fieldName) {
            return;
        }
        $this->assertFieldNameIsAllowed($fieldName, $path);
    }

    /**
     * @return string|array<string, mixed>
     */
    private function parseSortOptions(mixed $options, string $path): string|array
    {
        if (is_string($options)) {
            $this->assertSortOrderIsAllowed($options, $path);

            return $options;
        }
        if (!is_array($options)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($path, "string ('asc' | 'desc') | object", $options);
        }
        /** @psalm-suppress MixedAssignment */
        foreach ($options as $optionName => $optionValue) {
            if (!in_array($optionName, self::SORT_OPTIONS, true)) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Unsupported sort option '%s' at '%s', supported options are: %s.", (string) $optionName, $path, implode(', ', self::SORT_OPTIONS)));
            }
            if ('order' === $optionName) {
                $this->assertSortOrderIsAllowed($optionValue, sprintf('%s.order', $path));
            }
            if ('mode' === $optionName && (!is_string($optionValue) || !in_array($optionValue, self::SORT_MODES, true))) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('%s.mode', $path), implode(' | ', self::SORT_MODES), $optionValue);
            }
        }

        /** @var array<string, mixed> $options */
        return $options;
    }

    private function assertSortOrderIsAllowed(mixed $order, string $path): void
    {
        if (!is_string($order) || !in_array($order, self::SORT_ORDERS, true)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($path, implode(' | ', self::SORT_ORDERS), $order);
        }
    }
}
