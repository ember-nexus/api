<?php

declare(strict_types=1);

namespace App\Trait;

use LogicException;
use Symfony\Component\Console\Input\InputInterface;
use Throwable;

trait CommandInputTrait
{
    protected function createInvalidInputException(string $detail): Throwable
    {
        return new LogicException($detail);
    }

    protected function getStringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);
        if (!is_string($value)) {
            throw $this->createInvalidInputException(sprintf("Expected argument '%s' to be string, got %s.", $name, get_debug_type($value)));
        }

        return $value;
    }

    /**
     * Negatable options without a default are null when not provided.
     */
    protected function isOptionActive(InputInterface $input, string $name): bool
    {
        $value = $input->getOption($name) ?? false;
        if (!is_bool($value)) {
            throw $this->createInvalidInputException(sprintf("Expected option '%s' to be bool, got %s.", $name, get_debug_type($value)));
        }

        return $value;
    }

    protected function getNullableStringOption(InputInterface $input, string $name): ?string
    {
        $value = $input->getOption($name);
        if (null !== $value && !is_string($value)) {
            throw $this->createInvalidInputException(sprintf("Expected option '%s' to be string or null, got %s.", $name, get_debug_type($value)));
        }

        return $value;
    }
}
