<?php

declare(strict_types=1);

namespace App\EventSystem\SearchQuery\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use App\Type\SearchQueryType;

class SearchQueryEvent implements EventInterface
{
    use StoppableEventTrait;

    private array $result = [];

    public function __construct(
        private SearchQueryType $searchQueryType,
        private string|array $searchQuery,
        private array $parameters = []
    ) {
    }

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getSearchQueryType(): SearchQueryType
    {
        return $this->searchQueryType;
    }

    public function getSearchQuery(): string|array
    {
        return $this->searchQuery;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
