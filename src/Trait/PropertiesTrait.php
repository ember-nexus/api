<?php

declare(strict_types=1);

namespace App\Trait;

use Exception;

trait PropertiesTrait
{
    /**
     * @var array<string, mixed>
     */
    private array $properties = [];

    public function addProperty(string $name, mixed $value = null): static
    {
        $this->properties[$name] = $value;

        return $this;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param iterable<string, mixed> $properties
     */
    public function addProperties(iterable $properties): static
    {
        foreach ($properties as $name => $value) {
            $this->properties[$name] = $value;
        }

        return $this;
    }

    public function hasProperty(string $name): bool
    {
        return array_key_exists($name, $this->properties);
    }

    public function getProperty(string $name): mixed
    {
        if (!$this->hasProperty($name)) {
            throw new Exception(sprintf('Undefined array key "%s".', $name));
        }

        return $this->properties[$name];
    }

    /**
     * @throws Exception if the property does not exist or is not a string
     */
    public function getStringProperty(string $name): string
    {
        $value = $this->getProperty($name);
        if (!is_string($value)) {
            throw new Exception(sprintf('Expected property "%s" to be of type string, got %s.', $name, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @throws Exception if the property exists but is neither string nor null
     */
    public function getNullableStringProperty(string $name): ?string
    {
        if (!$this->hasProperty($name)) {
            return null;
        }
        $value = $this->getProperty($name);
        if (null !== $value && !is_string($value)) {
            throw new Exception(sprintf('Expected property "%s" to be of type string or null, got %s.', $name, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws Exception if the property does not exist or is not an array
     */
    public function getArrayProperty(string $name): array
    {
        $value = $this->getProperty($name);
        if (!is_array($value)) {
            throw new Exception(sprintf('Expected property "%s" to be of type array, got %s.', $name, get_debug_type($value)));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    public function removeProperty(string $name): static
    {
        unset($this->properties[$name]);

        return $this;
    }

    public function removeProperties(): static
    {
        $this->properties = [];

        return $this;
    }
}
