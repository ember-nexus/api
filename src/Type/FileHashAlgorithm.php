<?php

declare(strict_types=1);

namespace App\Type;

/**
 * Algorithms supported for `file.hash` entries: file content hashing, upload verification, and backup integrity
 * checks (see {@see \App\Service\FileHashService}).
 */
enum FileHashAlgorithm: string
{
    case SHA_256 = Hashes::SHA_256;

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
