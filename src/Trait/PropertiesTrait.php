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
