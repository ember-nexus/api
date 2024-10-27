<?php

declare(strict_types=1);

namespace App\Contract;

interface SearchStepResultInterface
{
    public function getResults(): array;

    public function setResults(array $results): self;

    public function getDebugData(): array;

    public function setDebugData(string $type, array $debugData): self;
}
