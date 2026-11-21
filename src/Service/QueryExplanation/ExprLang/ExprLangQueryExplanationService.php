<?php

declare(strict_types=1);

namespace App\Service\QueryExplanation\ExprLang;

use App\Service\QueryExplanation\QueryExplanationPayloadService;
use App\Type\QueryExplanation;
use Safe\Exceptions\JsonException;

/**
 * Explains failures of the go expr-lang CLI.
 *
 * Expr-lang is stateless and every input of an expression is visible to the user, therefore the compile and runtime
 * errors are safe to be passed on. The allowlist is still applied to keep the response shape explicit.
 */
class ExprLangQueryExplanationService
{
    public const string SOURCE = 'expr-lang';

    private const array DIAGNOSTICS_ALLOWLIST = ['kind', 'message', 'line', 'column'];

    public function __construct(
        private QueryExplanationPayloadService $queryExplanationPayloadService,
    ) {
    }

    /**
     * @param string $errorOutput raw error output of the expr cli, expected to be JSON like {"error": "..."}
     */
    public function explain(string $errorOutput): QueryExplanation
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $decoded = \Safe\json_decode($errorOutput, true);
        } catch (JsonException) {
            $decoded = null;
        }
        $error = null;
        if (is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])) {
            $error = $decoded['error'];
        }
        if (null === $error) {
            return new QueryExplanation(self::SOURCE, QueryExplanation::CODE_EXECUTION_FAILED, true);
        }

        $kind = null;
        if (1 === \Safe\preg_match('/^(compile error|runtime error):\s*/', $error, $kindMatches)) {
            /** @var array{1: string} $kindMatches */
            $kind = $kindMatches[1];
        }
        $data = ['kind' => $kind, 'message' => $error];
        if (1 === \Safe\preg_match('/\((\d+):(\d+)\)/', $error, $positionMatches)) {
            /** @var array{1: string, 2: string} $positionMatches */
            $data['line'] = (int) $positionMatches[1];
            $data['column'] = (int) $positionMatches[2];
        }

        return new QueryExplanation(
            self::SOURCE,
            'compile error' === $kind ? QueryExplanation::CODE_INVALID_QUERY : QueryExplanation::CODE_EXECUTION_FAILED,
            true,
            $this->queryExplanationPayloadService->sanitize($data, self::DIAGNOSTICS_ALLOWLIST),
            true
        );
    }
}
