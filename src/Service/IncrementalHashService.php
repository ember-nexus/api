<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client409ConflictExceptionFactory;
use HashContext;
use Throwable;

/**
 * Computes a hash of an upload incrementally, chunk by chunk, across separate requests: the running
 * `HashContext`'s serialized state is persisted on the `Upload` element between chunks (see UploadService), so
 * the final hash falls out of the upload itself once the last chunk is processed, without ever having to read
 * the finished file back from S3 afterwards.
 *
 * Each chunk's resource is hashed here, then rewound, before being handed to S3Service. This is a local
 * (memory/temp-file) read, not a network round trip - and not even the only such local read: AsyncAws's own S3
 * request signer already reads the whole resource twice on its own (once for a `content-md5` header, once for
 * `x-amz-content-sha256`), rewinding each time, before ever streaming it for real. That existing behavior is
 * also why this hashes explicitly instead of via a stream filter attached once and left in place: a filter would
 * see all of those extra internal reads too, not just the one that matters, and would hash the content multiple
 * times over.
 *
 * `HashContext` (as returned by `hash_init()`) is not a documented, BC-promised serialization target, but does
 * currently survive PHP's standard `serialize()`/`unserialize()` and resumes hashing correctly.
 */
class IncrementalHashService
{
    public function __construct(
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    public function createContext(string $algorithm): HashContext
    {
        return hash_init($algorithm);
    }

    /**
     * Hashes $resource (from its current position to EOF) into $context, then rewinds $resource, so that
     * whatever reads it next (e.g. S3Service, for the actual upload) sees the same, untouched content.
     *
     * @param resource $resource
     */
    public function updateFromResource(HashContext $context, $resource): void
    {
        hash_update_stream($context, $resource);
        \Safe\rewind($resource);
    }

    public function finalize(HashContext $context): string
    {
        return hash_final($context);
    }

    /**
     * Serializes a still-open (not yet finalized) hash context for storage between requests, e.g. as a property
     * on the `Upload` element. Base64-encoded, since the serialized form embeds the hash's raw internal buffer
     * (which may contain arbitrary bytes, not just printable ones) and must round-trip safely through storage
     * layers that expect text.
     */
    public function serializeContextForStorage(HashContext $context): string
    {
        return base64_encode(serialize($context));
    }

    /**
     * Restores a hash context previously serialized by {@see serializeContextForStorage()}. Since a client never
     * supplies this value directly (it is purely server-generated and stored in our own database), this is not
     * an "unserialize untrusted data" concern - but the serialized form of a HashContext is an undocumented PHP
     * internal representation with no BC promise, so decoding failure (e.g. after a PHP/OpenSSL version change
     * mid-upload, which is not realistically reproducible in a test) is handled explicitly rather than left to
     * crash. The same error path is also reachable, and is covered by a test, for plain data corruption.
     */
    public function unserializeContextFromStorage(string $serialized): HashContext
    {
        try {
            $binary = \Safe\base64_decode($serialized, true);
            $context = \Safe\unserialize($binary);
        } catch (Throwable) {
            $context = null;
        }

        if (!($context instanceof HashContext)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Could not resume this upload\'s integrity check; please restart the upload.');
        }

        return $context;
    }
}
