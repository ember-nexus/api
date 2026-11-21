<?php

declare(strict_types=1);

namespace App\Contract;

/**
 * Access to the memory mapped id -> type lookup table (`.mphf` file) of the `EmberNexus\Mphf` PHP extension.
 *
 * The table has no membership information: for an id which was not part of the build, {@see lookup()} usually returns
 * some (wrong) type. The result is therefore only a candidate which a typed query has to confirm.
 */
interface MphfInterface
{
    /**
     * False if the extension is not available, e.g. in images without it or in plain PHPUnit runs on the host.
     */
    public function isAvailable(): bool;

    /**
     * Maps the file and makes it the current table; idempotent per version.
     */
    public function load(string $path, string $version, bool $madviseRandom = true): bool;

    public function unload(): void;

    /**
     * Version of the loaded table, an empty string if nothing is loaded.
     */
    public function version(): string;

    /**
     * Candidate type name of the element id, an empty string if unknown or if nothing is loaded.
     */
    public function lookup(string $id): string;

    public function lastError(): string;

    /**
     * @return array<string, mixed>
     */
    public function info(): array;
}
