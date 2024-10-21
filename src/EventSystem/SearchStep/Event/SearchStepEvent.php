<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use App\Type\SearchStepType;

class SearchStepEvent implements EventInterface
{
    use StoppableEventTrait;

    /**
     * @var mixed[]
     */
    private array $results = [];
    /**
     * @var array<string, mixed>
     */
    private array $debugData = [];

    /**
     * @param string|array<string, mixed>|null $query
     * @param array<string, mixed>             $parameters
     */
    public function __construct(
        private SearchStepType $type,
        private string|array|null $query,
        private array $parameters,
    ) {
        $this->addDebugData(
            'input',
            [
                'type' => $this->type->value,
                'query' => $this->query,
                'parameters' => $this->parameters,
            ]
        );
    }

    public function getType(): SearchStepType
    {
        return $this->type;
    }

    /**
     * @return array<string, mixed>|string|null
     */
    public function getQuery(): array|string|null
    {
        return $this->query;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return mixed[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * @param mixed[] $results
     */
    public function setResults(array $results): self
    {
        $this->results = $results;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDebugData(): array
    {
        return $this->debugData;
    }

    public function addDebugData(string $key, mixed $data): self
    {
        $this->debugData[$key] = $data;

        return $this;
    }
}
