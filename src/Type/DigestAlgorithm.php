<?php

declare(strict_types=1);

namespace App\Type;

/**
 * Algorithms supported in the RFC 9530 `Repr-Digest` / `Content-Digest` headers (see
 * {@see \App\Service\DigestService}).
 */
enum DigestAlgorithm: string
{
    case SHA_256 = Hashes::SHA_256_DIGEST_LABEL;

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
