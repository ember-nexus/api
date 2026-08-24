<?php

declare(strict_types=1);

namespace App\Contract\S3;

/**
 * Hard technical limits imposed by an S3(-compatible) storage backend itself, as opposed to
 * {@see \EmberNexusBundle\Service\EmberNexusConfiguration}'s operator-configurable `file.*` settings.
 *
 * These values are not meant to be operator-configurable: they describe what the underlying storage protocol
 * actually allows, not what an operator wants to allow. Operator configuration may only be more restrictive than
 * these limits, never less; see {@see \App\Service\S3TechnicalLimitsValidator}.
 *
 * Only one implementation exists today ({@see \App\Type\S3\S3TechnicalLimits}, hardcoded to the standard AWS S3
 * multipart upload limits), as only a single S3 connection is supported. This interface exists so that, once
 * multiple storage backends/connections with differing technical limits are supported, additional
 * implementations can be introduced (and selected per connection) without changing any consumer of this
 * interface.
 */
interface S3TechnicalLimitsInterface
{
    public function getMinChunkSizeInBytes(): int;

    public function getMaxChunkCount(): int;

    public function getMaxObjectSizeInBytes(): int;
}
