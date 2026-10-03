<?php

declare(strict_types=1);

namespace App\Service;

use Throwable;

/**
 * Formats and parses RFC 9530 `Repr-Digest` / `Content-Digest` header values. Only `sha-256` is supported, other
 * algorithm members are ignored.
 */
class DigestService
{
    public const string ALGORITHM_LABEL = 'sha-256';

    public function formatDigestHeaderValue(string $hexHash): string
    {
        return sprintf('%s=:%s:', self::ALGORITHM_LABEL, base64_encode(\Safe\hex2bin($hexHash)));
    }

    /**
     * Returns the hex-encoded sha-256 hash, or null if the header contains no valid sha-256 member.
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
