<?php

declare(strict_types=1);

namespace App\Service\QueryExplanation\Elasticsearch;

use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Elasticsearch\Response\Elasticsearch;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Throwable;

/**
 * Diagnoses failed elasticsearch queries by validating the user's query fragment with `_validate/query`.
 *
 * - Validation only parses the query, so for invalid queries the reported error depends on the query text alone.
 * - The explanations which elasticsearch returns for valid queries are ignored on purpose: multi-term queries
 *   (wildcard, prefix, fuzzy, ...) expand to indexed terms and would leak stored data. `rewrite` is never used.
 * - Everything else (script errors, shard failures, ...) is reduced to allowlisted error types.
 *
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class ElasticsearchQueryExplanationService
{
    public const string SOURCE = 'elasticsearch';

    /**
     * Java exceptions whose message is known to only consist of the parsed query structure.
     */
    private const array SAFE_VALIDATION_EXCEPTIONS = [
        'org.elasticsearch.common.ParsingException',
        'org.elasticsearch.index.query.QueryShardException',
        'org.elasticsearch.xcontent.XContentParseException',
    ];

    /**
     * Error types which are safe to be returned without their reason.
     */
    private const array SAFE_ERROR_TYPES = [
        'parsing_exception',
        'x_content_parse_exception',
        'query_shard_exception',
        'illegal_argument_exception',
        'too_many_clauses',
        'search_phase_execution_exception',
        'script_exception',
        'circuit_breaking_exception',
        'index_not_found_exception',
    ];

    private const array DIAGNOSTICS_ALLOWLIST = [
        'code',
        'message',
        'status',
        'rootCauseCodes',
        'validation' => ['valid', 'exception'],
    ];

    public function __construct(
        private ElasticEntityManager $elasticEntityManager,
        private QueryExplanationPayloadService $queryExplanationPayloadService,
    ) {
    }

    /**
     * @return array{status: int|null, code: string|null, rootCauseCodes: string[]}
     *
     * @psalm-suppress MixedAssignment
     */
    private function parseFailureResponse(ClientResponseException|ServerResponseException $failure): array
    {
        $result = ['status' => null, 'code' => null, 'rootCauseCodes' => []];

        try {
            $response = $failure->getResponse();
            $result['status'] = $response->getStatusCode();
            $body = \Safe\json_decode((string) $response->getBody(), true);
        } catch (Throwable) {
            return $result;
        }
        $error = is_array($body) ? ($body['error'] ?? null) : null;
        if (!is_array($error)) {
            return $result;
        }

        $code = $error['type'] ?? null;
        if (is_string($code) && in_array($code, self::SAFE_ERROR_TYPES, true)) {
            $result['code'] = $code;
        }
        $rootCauses = is_array($error['root_cause'] ?? null) ? $error['root_cause'] : [];
        foreach ($rootCauses as $rootCause) {
            $rootCauseCode = is_array($rootCause) ? ($rootCause['type'] ?? null) : null;
            if (is_string($rootCauseCode) && in_array($rootCauseCode, self::SAFE_ERROR_TYPES, true)) {
                $result['rootCauseCodes'][] = $rootCauseCode;
            }
        }
        $result['rootCauseCodes'] = array_values(array_unique($result['rootCauseCodes']));

        return $result;
    }

    /**
     * @param array<string, mixed> $userQuery
     *
     * @return array{exception: string, message: string}|null null if the query is valid or the error can not be shown
     *
     * @psalm-suppress MixedAssignment
     */
    private function validate(array $userQuery, string $indices): ?array
    {
        try {
            /**
             * @psalm-suppress InvalidArgument
             */
            $response = $this->elasticEntityManager->getClient()->indices()->validateQuery([
                'index' => $indices,
                'explain' => true,
                'body' => ['query' => $userQuery],
            ]);
            if (!($response instanceof Elasticsearch)) {
                return null;
            }
            $data = $response->asArray();
        } catch (Throwable) {
            return null;
        }
        if (true === ($data['valid'] ?? null)) {
            // explanations of valid queries are deliberately ignored
            return null;
        }

        $error = $data['error'] ?? null;
        if (!is_string($error)) {
            // invalid on some indices only
            foreach (is_array($data['explanations'] ?? null) ? $data['explanations'] : [] as $explanation) {
                if (is_array($explanation) && is_string($explanation['error'] ?? null)) {
                    $error = $explanation['error'];
                    break;
                }
            }
        }
        if (!is_string($error)) {
            return null;
        }

        // format: "<java exception class>: <message>; <caused by class>: <message>"
        $parts = explode(': ', $error, 2);
        if (2 !== count($parts) || !in_array($parts[0], self::SAFE_VALIDATION_EXCEPTIONS, true)) {
            return null;
        }

        return ['exception' => $parts[0], 'message' => $parts[1]];
    }

    /**
     * Returns null if the failure was not caused by elasticsearch.
     *
     * @param array<string, mixed>|null $userQuery the user's query fragment after expressions got resolved, without the
     *                                             access restrictions which get mixed in
     */
    public function explain(Throwable $failure, ?array $userQuery, string $indices = '*'): ?QueryExplanation
    {
        if (!($failure instanceof ElasticsearchException)) {
            return null;
        }
        if (!($failure instanceof ClientResponseException) && !($failure instanceof ServerResponseException)) {
            return new QueryExplanation(self::SOURCE, QueryExplanation::CODE_EXECUTION_FAILED, false);
        }

        $data = $this->parseFailureResponse($failure);
        $isUserError = 400 === $data['status'];

        $validationError = ($isUserError && null !== $userQuery) ? $this->validate($userQuery, $indices) : null;
        if (null !== $validationError) {
            $data['validation'] = ['valid' => false, 'exception' => $validationError['exception']];
            $data['message'] = $validationError['message'];
        }

        return new QueryExplanation(
            self::SOURCE,
            null !== $validationError ? QueryExplanation::CODE_INVALID_QUERY : QueryExplanation::CODE_EXECUTION_FAILED,
            $isUserError,
            $this->queryExplanationPayloadService->sanitize(array_filter($data, static fn (mixed $value): bool => null !== $value), self::DIAGNOSTICS_ALLOWLIST),
            null !== $validationError
        );
    }
}
