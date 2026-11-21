<?php

declare(strict_types=1);

namespace App\Type;

use stdClass;

/**
 * Validated, normalized input of the Elasticsearch query DSL mixin search step.
 *
 * Both the shorthand (the query itself) and the extended definition (object with `query`, `highlight` and `sort`)
 * are normalized into this object.
 */
final class ElasticsearchQueryDefinition
{
    /**
     * @param array<string, mixed>                                                  $query     the user's Elasticsearch query clause
     * @param array<string, mixed>|null                                             $highlight Elasticsearch highlight options
     * @param list<string|array<string, string|array<string, mixed>|stdClass>>|null $sort      sort definition, normalized to a list
     */
    public function __construct(
        public readonly array $query,
        public readonly ?array $highlight = null,
        public readonly ?array $sort = null,
    ) {
    }
}
