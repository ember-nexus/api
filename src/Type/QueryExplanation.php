<?php

declare(strict_types=1);

namespace App\Type;

/**
 * Result of diagnosing a failed database / expression query.
 *
 * Everything in here has already passed an allowlist. Raw database output must never end up in this object, in
 * particular nothing which could contain stored data.
 */
final class QueryExplanation
{
    public const string CODE_INVALID_QUERY = 'invalid-query';
    public const string CODE_EXECUTION_FAILED = 'query-execution-failed';

    /**
     * @param string               $source        technology which was asked for the explanation, e.g. neo4j
     * @param string               $code          stable, ember nexus specific code, see CODE_* constants
     * @param bool                 $isUserError   true if the query is at fault, false if the server or database is
     * @param array<string, mixed> $diagnostics   allowlisted and size-limited data which was passed through from the database; the only place where database output may appear
     * @param bool                 $isMessageSafe whether $diagnostics['message'] may be shown to the user
     */
    public function __construct(
        public readonly string $source,
        public readonly string $code,
        public readonly bool $isUserError,
        public readonly array $diagnostics = [],
        public readonly bool $isMessageSafe = false,
    ) {
    }
}
