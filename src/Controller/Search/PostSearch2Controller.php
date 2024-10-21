<?php

declare(strict_types=1);

namespace App\Controller\Search;

use App\EventSystem\SearchQuery\Event\SearchQueryEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Response\JsonResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\SearchQueryType;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PostSearch2Controller extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private EventDispatcherInterface $eventDispatcher,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory
    ) {
    }

    #[Route(
        '/search2',
        name: 'post-search2',
        methods: ['POST']
    )]
    public function postSearch2(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        if (!array_key_exists('queries', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('queries', 'list with at least one query object');
        }
        $queries = $body['queries'];

        $previousResult = [];
        foreach ($queries as $query) {
            if (!array_key_exists('type', $query)) {
                throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('queries[].type', 'valid query type');
            }
            $type = $query['type'];
            if (!is_string($type)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('queries[].type', 'string', $type);
            }
            $type = SearchQueryType::from($type);
            if (!array_key_exists('query', $query)) {
                throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('queries[].query', 'valid query');
            }
            $searchQuery = $query['query'];
            $searchQueryEvent = new SearchQueryEvent($type, $searchQuery, $previousResult);
            $this->eventDispatcher->dispatch($searchQueryEvent);
            $previousResult = $searchQueryEvent->getResult();
        }
        $result = $previousResult;

        return new JsonResponse($result);
    }
}
