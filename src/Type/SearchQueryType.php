<?php

declare(strict_types=1);

namespace App\Type;

enum SearchQueryType: string
{
    case ELASTICSEARCH = 'elasticsearch';
    case CYPHER_SIMPLE = 'cypher-simple';
    case CYPHER_ADVANCED = 'cypher-advanced';
}
