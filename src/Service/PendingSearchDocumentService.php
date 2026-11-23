<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\Service\ResetInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface;

/**
 * Elasticsearch documents of elements which are created right now but not written yet. The search access of an element
 * can only be calculated once the element exists in the graph, which is after the create statements were flushed;
 * holding the documents back until then lets that calculation add its result to the document, so every element is
 * written to Elasticsearch once instead of being created and updated right away.
 */
class PendingSearchDocumentService implements ResetInterface
{
    /**
     * @var array<string, DocumentInterface>
     */
    private array $documents = [];

    public function hold(string $elementId, DocumentInterface $document): void
    {
        $this->documents[$elementId] = $document;
    }

    public function get(string $elementId): ?DocumentInterface
    {
        return $this->documents[$elementId] ?? null;
    }

    /**
     * @return array<string, DocumentInterface>
     */
    public function releaseAll(): array
    {
        $documents = $this->documents;
        $this->documents = [];

        return $documents;
    }

    public function reset(): void
    {
        $this->documents = [];
    }
}
