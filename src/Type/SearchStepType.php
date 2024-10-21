<?php

declare(strict_types=1);

namespace App\Type;

enum SearchStepType: string
{
    case ELEMENT_HYDRATION = 'element-hydration';
    case ELASTICSEARCH_QUERY_DSL_MIXIN = 'elasticsearch-query-dsl-mixin';
    case CYPHER_PATH_SUBSET = 'cypher-path-subset';
}
