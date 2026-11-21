<?php

declare(strict_types=1);

// Stub of the `EmberNexus\Mphf` PHP extension (lib/UuidToTypeMphfLookup/extension), for static analysis only. It is
// referenced by PHPStan (scanFiles) and Psalm (stubs) and is not loaded at runtime.

namespace EmberNexus\Mphf;

/**
 * Maps the `.mphf` file at $path as version $version; idempotent per version. Returns false on error, see lastError().
 */
function load(string $path, string $version, bool $madviseRandom): bool
{
}

/**
 * Back to "nothing loaded".
 */
function unload(): void
{
}

/**
 * Version of the loaded table, an empty string if nothing is loaded.
 */
function version(): string
{
}

/**
 * Candidate type name of an element id, an empty string if unknown or if nothing is loaded.
 */
function lookup(string $id): string
{
}

function lastError(): string
{
}

/**
 * JSON: version, loadedAt, createdAtMs, watermarkMs, keyCount, placedCount, fallbackCount, typeCount, valueWidth,
 * levels, gamma, bytes.
 */
function info(): string
{
}
