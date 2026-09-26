<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client409ConflictExceptionFactory;
use HashContext;
use Throwable;

/**
 * Hashes an upload chunk by chunk across requests; the serialized hash context is stored on the `Upload` element
 * between chunks, so the finished file never has to be read back from S3.
 *
 * Chunks are hashed explicitly instead of via a stream filter, as AsyncAws's request signer reads the resource
 * multiple times, which a filter would hash as well.
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
     * Rewinds $resource afterwards, so that it can be uploaded next.
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
     * Base64-encoded, as the serialized context contains binary data.
     */
    public function serializeContextForStorage(HashContext $context): string
    {
        return base64_encode(serialize($context));
    }

    /**
     * The value is server-generated, but the serialization format of HashContext has no BC promise (e.g. across
     * PHP updates), so failures are handled explicitly.
     */
    public function unserializeContextFromStorage(string $serialized): HashContext
    {
        try {
            $binary = \Safe\base64_decode($serialized, true);
            $context = \Safe\unserialize($binary, ['allowed_classes' => [HashContext::class]]);
        } catch (Throwable) {
            $context = null;
        }

        if (!($context instanceof HashContext)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Could not resume this upload\'s integrity check; please restart the upload.');
        }

        return $context;
    }
}
