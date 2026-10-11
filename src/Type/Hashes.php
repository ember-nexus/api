<?php

declare(strict_types=1);

namespace App\Type;

/**
 * Single source of truth for hash algorithm identifiers; the same algorithm is spelled differently depending on
 * where it's used (PHP's `hash()` vs. RFC 9530 Repr-Digest/Content-Digest member names), so each spelling gets its
 * own constant here instead of being duplicated as a literal.
 */
final class Hashes
{
    /** PHP `hash()`-compatible algorithm identifier. */
    public const string SHA_256 = 'sha256';

    /** RFC 9530 Repr-Digest/Content-Digest algorithm member name for the same algorithm. */
    public const string SHA_256_DIGEST_LABEL = 'sha-256';
}
