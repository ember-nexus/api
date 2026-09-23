<?php

declare(strict_types=1);

namespace App\Service;

use ArrayAccess;
use Traversable;

/**
 * Computes a cryptographic content hash of an uploaded file.
 *
 * S3 does not provide a usable, standardized content hash: the `ETag` of a simple upload happens to be its MD5,
 * but the `ETag` of a file assembled via a multipart upload (i.e. every resumable upload completed through
 * `S3Service::mergeFileChunks()`) is a hash of the parts' ETags, not a hash of the file's actual content, and MD5
 * is not collision-resistant regardless. There is therefore no way around reading the assembled file once, after
 * it was written to the storage bucket, to compute a real content hash.
 *
 * SHA-256 is used for now: it is cryptographically secure, and, backed by PHP's `hash` extension (which uses
 * OpenSSL's optimized, often hardware-accelerated implementation), comfortably exceeds the throughput of a
 * 1 GiB/s uplink on commodity hardware. BLAKE3 is preferred long-term, but the only pure-PHP implementation
 * available today is several orders of magnitude too slow to be usable; a high-performance BLAKE3 (native PHP
 * extension or FFI binding to a native library) needs a Docker image change first.
 *
 * The `file.hash` property is stored as `{<algorithm>: <hex digest>}` rather than a flat pair, specifically so a
 * future algorithm can be added (or this one replaced) without a data migration.
 */
class FileHashService
{
    public const string ALGORITHM = 'sha256';

    /**
     * Algorithms the API natively writes and verifies. `file.hash` entries of any other algorithm are ignored.
     */
    public const array SUPPORTED_ALGORITHMS = [self::ALGORITHM];
    private const int READ_CHUNK_SIZE = 1024 * 1024;

    /**
     * @param resource $resource
     */
    public function calculateHashFromResource($resource): string
    {
        $context = hash_init(self::ALGORITHM);
        hash_update_stream($context, $resource);

        return hash_final($context);
    }

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
     * Extracts the `{<algorithm>: <hex digest>}` entries of a `file` property's `hash` object whose algorithm is in
     * {@see self::SUPPORTED_ALGORITHMS}. Entries of other algorithms and malformed entries are ignored.
     *
     * `file` may be read back from MongoDB, in which case nested values can still be BSONDocument (ArrayAccess and
     * Traversable) instances instead of plain arrays.
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
