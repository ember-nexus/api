<?php

declare(strict_types=1);

namespace App\Service\Index;

/**
 * Outcome of one run of {@see IndexEnsurer}.
 */
final class IndexEnsureResult
{
    /**
     * @param string[] $created    names of indices which were created (or would be, in a dry run)
     * @param string[] $existing   names of managed indices which already existed
     * @param string[] $equivalent descriptions of types which already have an equivalent index under another name
     */
    public function __construct(
        private array $created = [],
        private array $existing = [],
        private array $equivalent = [],
    ) {
    }

    /**
     * @return string[]
     */
    public function getCreated(): array
    {
        return $this->created;
    }

    /**
     * @return string[]
     */
    public function getExisting(): array
    {
        return $this->existing;
    }

    /**
     * @return string[]
     */
    public function getEquivalent(): array
    {
        return $this->equivalent;
    }
}
