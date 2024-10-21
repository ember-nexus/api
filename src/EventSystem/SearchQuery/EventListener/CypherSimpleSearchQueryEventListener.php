<?php

declare(strict_types=1);

namespace App\EventSystem\SearchQuery\EventListener;

use App\EventSystem\SearchQuery\Event\SearchQueryEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Type\SearchQueryType;
use Psr\Log\LoggerInterface;

class CypherSimpleSearchQueryEventListener
{
    public function __construct(
        private LoggerInterface $logger,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function onSearchQueryEvent(SearchQueryEvent $event): void
    {
        if (SearchQueryType::CYPHER_SIMPLE !== $event->getSearchQueryType()) {
            return;
        }

        $query = $event->getSearchQuery();
        if (!is_string($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'cypher query string', \Safe\json_encode($query));
        }

        $this->logger->debug(
            'Executing simple cypher search query.',
            [
                'query' => $query,
            ]
        );

        $event->setResult([
            'hello' => 'world :D',
        ]);
        $event->stopPropagation();
    }
}
