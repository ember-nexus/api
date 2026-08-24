<?php

declare(strict_types=1);

namespace App\Service;

use Throwable;

/**
 * Implements the parts of RFC 9530 ("Digest Fields") relevant to file integrity: formatting a `Repr-Digest` /
 * `Content-Digest` response header from a stored hash, and parsing a client-supplied `Repr-Digest` /
 * `Content-Digest` request header for verification against an uploaded file.
 *
 * Only the `sha-256` algorithm (RFC 9530's registered label for our stored `file.hash.sha256`) is supported.
 * Digest field values are RFC 8941 Dictionaries of `<algorithm>=:<base64 digest>:` members; only that single
 * member is looked for, other (unsupported) algorithm members in the same header are ignored.
 */
class DigestService
{
    public const string ALGORITHM_LABEL = 'sha-256';

    public function formatDigestHeaderValue(string $hexHash): string
    {
        return sprintf('%s=:%s:', self::ALGORITHM_LABEL, base64_encode(\Safe\hex2bin($hexHash)));
    }

    /**
     * Returns the hex-encoded sha-256 hash declared in the header value, or null if the header does not declare a
     * usable sha-256 member (either only unsupported algorithms were offered, or the sha-256 value is malformed).
     */
    public function parseSha256HexFromHeaderValue(string $headerValue): ?string
    {
        if (1 !== \Safe\preg_match('/(?:^|,)\s*sha-256=:([A-Za-z0-9+\/=]*):/', $headerValue, $matches)) {
            return null;
        }

        $base64Value = $matches[1] ?? '';

        try {
            $binary = \Safe\base64_decode($base64Value, true);
        } catch (Throwable) {
            return null;
        }

        if (32 !== strlen($binary)) {
            return null;
        }

        return bin2hex($binary);
    }
}
