<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\SearchStepResultInterface;

class SearchStepResult implements SearchStepResultInterface
{
    private array $results = [];
    private array $debugData = [];

    public function __construct()
    {
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function setResults(array $results): SearchStepResultInterface
    {
        $this->results = $results;

        return $this;
    }

    public function getDebugData(): array
    {
        return $this->debugData;
    }

    public function setDebugData(array $debugData): SearchStepResultInterface
    {
        $this->debugData = $debugData;

        return $this;
    }
}
