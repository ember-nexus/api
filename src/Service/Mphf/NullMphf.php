<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Contract\MphfInterface;

/**
 * Used when the extension is missing or disabled: nothing is ever loaded, so the type service falls back to the
 * overlay and untyped queries.
 */
class NullMphf implements MphfInterface
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function load(string $path, string $version, bool $madviseRandom = true): bool
    {
        return false;
    }

    public function unload(): void
    {
    }

    public function version(): string
    {
        return '';
    }

    public function lookup(string $id): string
    {
        return '';
    }

    public function lastError(): string
    {
        return 'The EmberNexus\Mphf extension is not available.';
    }

    public function info(): array
    {
        return [];
    }
}
