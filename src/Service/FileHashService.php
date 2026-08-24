<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Computes a cryptographic content hash of an uploaded file.
 *
 * S3 does not provide a usable, standardized content hash: the `ETag` of a simple upload happens to be its MD5,
 * but the `ETag` of a file assembled via a multipart upload (i.e. every resumable upload completed through
 * `S3Service::mergeFileChunks()`) is a hash of the parts' ETags, not a hash of the file's actual content, and MD5
 * is not collision-resistant regardless. There is therefore no way around reading the assembled file once, after
 * it was written to the storage bucket, to compute a real content hash.
 *
 * SHA-256 is used: it is cryptographically secure, and, backed by PHP's `hash` extension (which uses OpenSSL's
 * optimized, often hardware-accelerated implementation), comfortably exceeds the throughput of a 1 GiB/s uplink
 * on commodity hardware.
 */
class FileHashService
{
    public const string ALGORITHM = 'sha256';

    /**
     * @param resource $resource
     */
    public function calculateHashFromResource($resource): string
    {
        $context = hash_init(self::ALGORITHM);
        hash_update_stream($context, $resource);

        return hash_final($context);
    }
}
