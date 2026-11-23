<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Contract\MphfInterface;

use function EmberNexus\Mphf\info;
use function EmberNexus\Mphf\lastError;
use function EmberNexus\Mphf\load;
use function EmberNexus\Mphf\lookup;
use function EmberNexus\Mphf\unload;
use function EmberNexus\Mphf\version;
use function Safe\json_decode;

/**
 * The only class which calls the functions of the `EmberNexus\Mphf` FrankenPHP extension (see
 * lib/UuidToTypeMphfLookup/extension). Only create it if the extension exists, see {@see MphfFactory}.
 *
 * @codeCoverageIgnore
 */
class GoMphf implements MphfInterface
{
    public function isAvailable(): bool
    {
        return true;
    }

    public function load(string $path, string $version, bool $madviseRandom = true): bool
    {
        return load($path, $version, $madviseRandom);
    }

    public function unload(): void
    {
        unload();
    }

    public function version(): string
    {
        return version();
    }

    public function lookup(string $id): string
    {
        return lookup($id);
    }

    public function lastError(): string
    {
        return lastError();
    }

    public function info(): array
    {
        /** @var array<string, mixed> $info */
        $info = json_decode(info(), true);

        return $info;
    }
}
