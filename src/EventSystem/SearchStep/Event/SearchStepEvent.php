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
     * @var array<string, mixed>|null
     */
    private ?array $resolvedQuery = null;

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

    /**
     * The user's query after expressions got resolved, without anything which gets mixed in by the server (e.g. access
     * restrictions). Null as long as the step handler did not resolve it.
     *
     * @return array<string, mixed>|null
     */
    public function getResolvedQuery(): ?array
    {
        return $this->resolvedQuery;
    }

    /**
     * @param array<string, mixed> $resolvedQuery
     */
    public function setResolvedQuery(array $resolvedQuery): self
    {
        $this->resolvedQuery = $resolvedQuery;

        return $this;
    }
}
