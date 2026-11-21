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
     * @var array<string, array<string, mixed>>
     */
    private array $elementMetadata = [];

    /**
     * @param string|array<string, mixed>|null                $query
     * @param array<string, mixed>                            $parameters
     * @param array<string, array<int, array<string, mixed>>> $previousElementMetadata metadata of elements which was added by previous steps: element id => step index => metadata
     */
    public function __construct(
        private SearchStepType $type,
        private string|array|null $query,
        private array $parameters,
        private array $previousElementMetadata = [],
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

    /**
     * Metadata of elements which got added by previous steps, e.g. the Elasticsearch score or highlights. Indexed by
     * element id and the index of the step which added the metadata, so that the information survives steps which
     * do not know about it.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getPreviousElementMetadata(): array
    {
        return $this->previousElementMetadata;
    }

    /**
     * Metadata which this step adds to elements, indexed by element id. The search controller makes it available to
     * all later steps, element hydration returns it next to the elements.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getElementMetadata(): array
    {
        return $this->elementMetadata;
    }

    /**
     * @param array<string, array<string, mixed>> $elementMetadata
     */
    public function setElementMetadata(array $elementMetadata): self
    {
        $this->elementMetadata = $elementMetadata;

        return $this;
    }
}
