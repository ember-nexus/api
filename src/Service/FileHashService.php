<?php

declare(strict_types=1);

namespace App\Service;

use ArrayAccess;
use Traversable;

/**
 * S3 ETags can not be used as content hashes: multipart upload ETags are derived from the parts' ETags, and MD5
 * is not collision-resistant anyway.
 *
 * `file.hash` is stored as `{<algorithm>: <hex digest>}`, so that algorithms can be added or replaced later (e.g.
 * BLAKE3, once a fast implementation is available) without a data migration.
 */
class FileHashService
{
    public const string ALGORITHM = 'sha256';

    /**
     * `file.hash` entries of other algorithms are ignored.
     */
    public const array SUPPORTED_ALGORITHMS = [self::ALGORITHM];
    private const int READ_CHUNK_SIZE = 1024 * 1024;

    /**
     * Calculates the hashes of all given algorithms in a single pass over $resource.
     *
     * @param resource $resource
     * @param string[] $algorithms
     *
     * @return array<string, string> algorithm => lowercase hex digest
     */
    public function calculateHashesFromResource($resource, array $algorithms): array
    {
        $contexts = [];
        foreach ($algorithms as $algorithm) {
            $contexts[$algorithm] = hash_init($algorithm);
        }
        while (!feof($resource)) {
            $chunk = \Safe\fread($resource, self::READ_CHUNK_SIZE);
            foreach ($contexts as $context) {
                hash_update($context, $chunk);
            }
        }

        $hashes = [];
        foreach ($contexts as $algorithm => $context) {
            $hashes[$algorithm] = hash_final($context);
        }

        return $hashes;
    }

    /**
     * Returns the supported entries of `file.hash`; other and malformed entries are ignored.
     * Values read from MongoDB can be BSONDocument instances instead of plain arrays.
     *
     * @return array<string, string> algorithm => lowercase hex digest
     */
    public function getVerifiableHashesFromFileProperty(mixed $fileProperty): array
    {
        if (!is_array($fileProperty) && !($fileProperty instanceof ArrayAccess)) {
            return [];
        }
        $hash = $fileProperty['hash'] ?? null;
        if ($hash instanceof Traversable) {
            $hash = iterator_to_array($hash);
        }
        if (!is_array($hash)) {
            return [];
        }

        $hashes = [];
        foreach ($hash as $algorithm => $value) {
            if (!is_string($algorithm) || !is_string($value)) {
                continue;
            }
            if (!in_array($algorithm, self::SUPPORTED_ALGORITHMS, true)) {
                continue;
            }
            $hashes[$algorithm] = strtolower($value);
        }

        return $hashes;
    }
}
