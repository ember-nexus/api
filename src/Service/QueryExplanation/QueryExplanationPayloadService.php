<?php

declare(strict_types=1);

namespace App\Service\QueryExplanation;

/**
 * Shared guard rails for everything which is passed through from a database to the user: only explicitly allowlisted
 * properties survive and the result is size-limited.
 *
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 */
class QueryExplanationPayloadService
{
    public const int MAX_DIAGNOSTICS_BYTES = 16384;

    private function isListOfScalars(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }

        return array_all($value, static fn (mixed $item): bool => is_scalar($item) || null === $item);
    }

    /**
     * Keeps only allowlisted properties which hold scalars (or null) or lists of scalars.
     *
     * Entries of $allowlist are either a property name (leaf, scalar values only) or `name => nested allowlist`, which
     * is applied to the nested map or to every map inside the nested list.
     *
     * @param array<mixed>             $data
     * @param array<int|string, mixed> $allowlist
     *
     * @return array<string, mixed>
     *
     * @psalm-suppress MixedAssignment, MixedArgumentTypeCoercion
     */
    public function allowlist(array $data, array $allowlist): array
    {
        $result = [];
        foreach ($allowlist as $key => $nestedAllowlist) {
            $name = is_int($key) ? $nestedAllowlist : $key;
            if (!is_string($name) || !array_key_exists($name, $data)) {
                continue;
            }
            $value = $data[$name];
            if (is_int($key)) {
                if (is_scalar($value) || null === $value || $this->isListOfScalars($value)) {
                    $result[$name] = $value;
                }
                continue;
            }
            if (!is_array($nestedAllowlist) || !is_array($value)) {
                continue;
            }
            if (array_is_list($value)) {
                $result[$name] = array_values(array_map(
                    fn (array $item): array => $this->allowlist($item, $nestedAllowlist),
                    array_filter($value, is_array(...))
                ));
                continue;
            }
            $result[$name] = $this->allowlist($value, $nestedAllowlist);
        }

        return $result;
    }

    /**
     * Replaces data which exceeds the size limit with a truncated excerpt of its JSON representation.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function truncate(array $data, int $maxBytes = self::MAX_DIAGNOSTICS_BYTES): array
    {
        $json = \Safe\json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (strlen($json) <= $maxBytes) {
            return $data;
        }

        return [
            'truncated' => true,
            'originalSizeInBytes' => strlen($json),
            'excerpt' => mb_strcut($json, 0, $maxBytes),
        ];
    }

    /**
     * @param array<mixed>             $data
     * @param array<int|string, mixed> $allowlist
     *
     * @return array<string, mixed>
     */
    public function sanitize(array $data, array $allowlist): array
    {
        return $this->truncate($this->allowlist($data, $allowlist));
    }
}
