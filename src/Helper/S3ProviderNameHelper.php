<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * Resolves the S3 "Server" response header to a short, safe-to-print provider name.
 */
class S3ProviderNameHelper
{
    public const string UNKNOWN_PROVIDER_NAME = 'Unknown';

    /**
     * Maps a lowercase prefix of a known "Server" header value to its canonical, human-readable name.
     * Lookup happens on prefixes, since some servers (e.g. SeaweedFS) append extra, non-identifying
     * details like free disk space or version to the header value.
     *
     * @var array<string, string>
     */
    private const array KNOWN_PROVIDER_NAMES = [
        'minio' => 'MinIO',
        'versitygw' => 'VersityGW',
        'seaweedfs' => 'SeaweedFS',
    ];

    /**
     * @psalm-suppress PossiblyInvalidArgument $sanitized is always a string, \Safe\preg_replace() only
     *                                          returns an array if the subject itself is an array
     */
    public static function normalize(?string $rawServerHeader): string
    {
        if (null === $rawServerHeader || '' === trim($rawServerHeader)) {
            return self::UNKNOWN_PROVIDER_NAME;
        }

        $lowercaseHeader = strtolower($rawServerHeader);
        foreach (self::KNOWN_PROVIDER_NAMES as $prefix => $providerName) {
            if (str_starts_with($lowercaseHeader, $prefix)) {
                return $providerName;
            }
        }

        $sanitized = \Safe\preg_replace('/[^A-Za-z0-9 ._-]/', '', $rawServerHeader);
        $sanitized = \Safe\preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = substr(trim($sanitized), 0, 64);

        return '' === $sanitized ? self::UNKNOWN_PROVIDER_NAME : $sanitized;
    }
}
