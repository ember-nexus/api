<?php

declare(strict_types=1);

namespace App\Contract;

interface SearchStepInterface
{
    public function isDangerous(): bool;

    public function getIdentifier(): string;

    public function executeStep(string|array $query, array $parameters): SearchStepResultInterface;
}
