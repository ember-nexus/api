<?php

declare(strict_types=1);

namespace App\Contract;

interface HasPropertiesInterface
{
    public function addProperty(string $name, mixed $value = null): self;

    /**
     * @param iterable<string, mixed> $properties
     */
    public function addProperties(iterable $properties): self;

    public function hasProperty(string $name): bool;

    public function getProperty(string $name): mixed;

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array;

    public function removeProperty(string $name): self;

    public function removeProperties(): self;
}
