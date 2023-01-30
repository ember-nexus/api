<?php

namespace App\Service;

use App\Response\CollectionResponse;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CollectionService
{
    public function __construct(
        private RequestStack $requestStack,
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private ParameterBagInterface $bag
    ) {
    }

    public function getCurrentPage(): int
    {
        $firstPage = 1;
        $query = $this->requestStack->getCurrentRequest()->query;
        if (!$query->has('page')) {
            return $firstPage;
        }
        $currentPage = (int) $query->get('page');
        if ($currentPage < $firstPage) {
            return $firstPage;
        }

        return $currentPage;
    }

    public function getPageSize(): int
    {
        $defaultPageSize = $this->bag->get('pageSize')['default'];
        $minPageSize = $this->bag->get('pageSize')['min'];
        $maxPageSize = $this->bag->get('pageSize')['max'];
        $query = $this->requestStack->getCurrentRequest()->query;
        if (!$query->has('pageSize')) {
            return $defaultPageSize;
        }
        $requestPageSize = (int) $query->get('pageSize');
        if ($requestPageSize < 1 || $requestPageSize < $minPageSize) {
            return $minPageSize;
        }
        if ($requestPageSize > $maxPageSize) {
            return $maxPageSize;
        }

        return $requestPageSize;
    }

    public function getTotalPages(int $totalElements = 0): int
    {
        return ceil(((float) $totalElements) / ((float) $this->getPageSize()));
    }

    /**
     * @param int|null $page if null, then the current page is used
     */
    public function getPageLink(?int $page = null): string
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $basePath = $currentRequest->getBasePath();

        if ('' === $basePath) {
            $basePath = '/';
        }

        $params = [];

        if (null === $page) {
            $page = $this->getCurrentPage();
        }
        if ($page > 1) {
            $params['page'] = $page;
        }

        $pageSize = $this->getPageSize();
        if ($pageSize !== $this->bag->get('pageSize')['default']) {
            $params['pageSize'] = $pageSize;
        }

        if ($currentRequest->query->has('sort')) {
            $params['sort'] = $currentRequest->query->get('sort');
        }

        if ($currentRequest->query->has('filter')) {
            $params['filter'] = $currentRequest->query->get('filter');
        }

        if ($currentRequest->query->has('query')) {
            $params['query'] = $currentRequest->query->get('query');
        }

        $queryString = [];
        foreach ($params as $name => $param) {
            $queryString[] = sprintf(
                '%s=%s',
                $name,
                urlencode($param)
            );
        }
        $queryString = implode('&', $queryString);
        if (strlen($queryString) > 0) {
            $queryString = '?'.$queryString;
        }

        return sprintf(
            '%s%s',
            $basePath,
            $queryString
        );
    }

    /**
     * @param UuidInterface[] $nodeUuids
     * @param UuidInterface[] $relationUuids
     */
    public function buildCollectionFromUuids(
        array $nodeUuids = [],
        array $relationUuids = [],
        int $totalNodes = 0
    ): CollectionResponse {
        $currentRequest = $this->requestStack->getCurrentRequest();
        $currentRequest->getBasePath();

        $nodeData = [];
        $relationData = [];

        foreach ($nodeUuids as $nodeUuid) {
            $nodeData[] = $this->elementToRawService->elementToRaw(
                $this->elementManager->getNode($nodeUuid)
            );
        }
        foreach ($relationUuids as $relationUuid) {
            $relationData[] = $this->elementToRawService->elementToRaw(
                $this->elementManager->getRelation($relationUuid)
            );
        }

        $currentPageId = $currentRequest->getRequestUri();
        if (!str_starts_with($currentPageId, '/')) {
            $currentPageId = sprintf(
                '/%s',
                $currentPageId
            );
        }

        $totalPages = $this->getTotalPages($totalNodes);
        $currentPage = $this->getCurrentPage();
        $previousPageLink = null;
        if ($currentPage > 1 && $currentPage <= $totalPages) {
            $previousPageLink = $this->getPageLink($currentPage - 1);
        }
        $nextPageLink = null;
        if ($currentPage < $totalPages) {
            $nextPageLink = $this->getPageLink($currentPage + 1);
        }

        return new CollectionResponse(
            [
                '@type' => '_PartialCollection',
                '@id' => $this->getPageLink(),
                'totalNodes' => $totalNodes,
                'links' => [
                    'first' => $this->getPageLink(1),
                    'previous' => $previousPageLink,
                    'next' => $nextPageLink,
                    'last' => $this->getPageLink($totalPages),
                ],
                'nodes' => $nodeData,
                'relations' => $relationData,
            ]
        );
    }
}
