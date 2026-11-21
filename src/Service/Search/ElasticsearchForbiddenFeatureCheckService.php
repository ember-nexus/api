<?php

declare(strict_types=1);

namespace App\Service\Search;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;

/**
 * Rejects Elasticsearch features which would allow users to look at data beyond the elements they have search access
 * to, or which would execute user-provided code.
 *
 * - Scripts of any kind.
 * - Lookups of other documents: terms lookups, document references of `more_like_this`, indexed shapes, percolation.
 * - Joining queries (`nested`, `has_child`, `has_parent`, `parent_id`). They are not usable with the automatically
 *   managed indices as of now, but must stay unavailable once custom index definitions are possible.
 * - `wrapper` queries, which would hide any of the above from this check.
 *
 * Highlight queries are queries like any other: they are checked as well, but not rejected as a whole.
 *
 * Elasticsearch's query DSL has no formal grammar, so this is a check of key names on any level of the structure, not
 * a full validation. The consequence is that fields which are named like one of these keys (or contain `script` as a
 * word, e.g. `my_script`) can not be queried.
 */
class ElasticsearchForbiddenFeatureCheckService
{
    private const array FORBIDDEN_KEYS = [
        'wrapper' => 'wrapper queries could hide forbidden features',
        'indexed_shape' => 'pre-indexed shapes are looked up from other documents',
        'percolate' => 'percolate queries read stored queries from other documents',
        'has_child' => 'joining queries are not supported',
        'has_parent' => 'joining queries are not supported',
        'parent_id' => 'joining queries are not supported',
        'nested' => 'joining queries are not supported',
    ];

    private const array SCRIPT_WORDS = ['script', 'scripts', 'scripted'];
    private const string SCRIPT_REASON = 'scripts are not supported';

    public function __construct(
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws Client400BadContentException
     */
    public function assertNoForbiddenFeatures(array $data, string $path): void
    {
        $this->walk($data, $path, null);
    }

    private function createException(string $feature, string $path, string $reason): Client400BadContentException
    {
        return $this->client400BadContentExceptionFactory->createFromDetail(
            sprintf("The Elasticsearch feature '%s' at '%s' is not supported: %s.", $feature, $path, $reason)
        );
    }

    /**
     * @param array<array-key, mixed> $data
     */
    private function walk(array $data, string $path, ?string $parentKey): void
    {
        /** @psalm-suppress MixedAssignment */
        foreach ($data as $key => $value) {
            if (is_int($key)) {
                $keyPath = sprintf('%s[%d]', $path, $key);
                $keyName = null;
            } else {
                $keyPath = sprintf('%s.%s', $path, $key);
                $keyName = $key;
                $this->assertKeyIsAllowed($key, $keyPath, $parentKey);
            }
            if (!is_array($value)) {
                continue;
            }
            if (null !== $keyName) {
                $this->assertValueIsAllowed($keyName, $value, $keyPath, $parentKey);
            }
            $this->walk($value, $keyPath, $keyName ?? $parentKey);
        }
    }

    /**
     * Matches `script`, `script_score`, `script_fields`, `_script`, `minimum_should_match_script` and similar, but not
     * keys which merely contain the letters, like `description`.
     */
    private function isScriptKey(string $key): bool
    {
        /** @psalm-suppress MixedAssignment */
        foreach (\Safe\preg_split('/[_.\-]/', strtolower($key)) as $part) {
            if (in_array($part, self::SCRIPT_WORDS, true)) {
                return true;
            }
        }

        return false;
    }

    private function assertKeyIsAllowed(string $key, string $keyPath, ?string $parentKey): void
    {
        if (array_key_exists($key, self::FORBIDDEN_KEYS)) {
            throw $this->createException($key, $keyPath, self::FORBIDDEN_KEYS[$key]);
        }
        if ($this->isScriptKey($key)) {
            throw $this->createException($key, $keyPath, self::SCRIPT_REASON);
        }
        if ('more_like_this' === $parentKey && in_array($key, ['docs', 'ids'], true)) {
            throw $this->createException($key, $keyPath, 'documents are looked up by id');
        }
    }

    /**
     * @param array<array-key, mixed> $value
     */
    private function assertValueIsAllowed(string $key, array $value, string $keyPath, ?string $parentKey): void
    {
        if ('terms' === $key) {
            // `terms` contains `{field: [values]}`, a terms lookup is `{field: {index, id, path}}`
            /** @psalm-suppress MixedAssignment */
            foreach ($value as $field => $fieldValue) {
                if (is_array($fieldValue) && !array_is_list($fieldValue)) {
                    throw $this->createException('terms lookup', sprintf('%s.%s', $keyPath, (string) $field), 'terms lookups read values from other documents');
                }
            }
        }
        if ('more_like_this' === $parentKey && ('like' === $key || 'unlike' === $key)) {
            // texts are fine, references to documents are not
            if (!array_is_list($value)) {
                throw $this->createException('more_like_this document reference', $keyPath, 'documents are looked up by id or provided artificially');
            }
            /** @psalm-suppress MixedAssignment */
            foreach ($value as $index => $item) {
                if (is_array($item)) {
                    throw $this->createException('more_like_this document reference', sprintf('%s[%d]', $keyPath, $index), 'documents are looked up by id or provided artificially');
                }
            }
        }
    }
}
