<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\CollectionResponse;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
class CollectionService
{
    public function __construct(
        private RequestStack $requestStack,
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Server500InternalServerErrorExceptionFactory $server500InternalServerErrorExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function getCurrentPage(): int
    {
        $firstPage = 1;
        $query = $this->requestStack->getCurrentRequest()?->query;
        if (!($query instanceof InputBag)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Query must be an instance of InputBag.', ['query' => $query]);
        }
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
        $query = $this->requestStack->getCurrentRequest()?->query;
        if (!($query instanceof InputBag)) {
            throw $this->server500InternalServerErrorExceptionFactory->createFromTemplate('Query must be an instance of InputBag.', ['query' => $query]);
        }
        if (!$query->has('pageSize')) {
            return $this->emberNexusConfiguration->getPageSizeDefault();
        }
        $requestPageSize = (int) $query->get('pageSize');
        if ($requestPageSize < $this->emberNexusConfiguration->getPageSizeMin()) {
            return $this->emberNexusConfiguration->getPageSizeMin();
        }
        if ($requestPageSize > $this->emberNexusConfiguration->getPageSizeMax()) {
            return $this->emberNexusConfiguration->getPageSizeMax();
        }

        return $requestPageSize;
    }

    public function getTotalPages(int $totalElements = 0): int
    {
        return (int) ceil(((float) $totalElements) / ((float) $this->getPageSize()));
    }

    /**
     * @param int|null $page if null, then the current page is used
     */
    public function getPageLink(?int $page = null): string
    {
        $currentRequest = $this->requestStack->getCurrentRequest();
        if (null === $currentRequest) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Current request can not be null.');
        }
        $basePath = $currentRequest->getPathInfo();

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
        if ($pageSize !== $this->emberNexusConfiguration->getPageSizeDefault()) {
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
                urlencode((string) $param)
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
     * @param UuidInterface[] $nodeIds
     * @param UuidInterface[] $relationIds
     */
    public function buildCollectionFromIds(
        array $nodeIds = [],
        array $relationIds = [],
        int $totalNodes = 0,
    ): CollectionResponse {
        $nodeData = [];
        $relationData = [];

        foreach ($nodeIds as $nodeId) {
            $nodeElement = $this->elementManager->getNode($nodeId);
            if ($nodeElement) {
                $nodeData[] = $this->elementToRawService->elementToRaw(
                    $nodeElement
                );
            }
        }
        foreach ($relationIds as $relationId) {
            $relationElement = $this->elementManager->getRelation($relationId);
            if ($relationElement) {
                $relationData[] = $this->elementToRawService->elementToRaw(
                    $relationElement
                );
            }
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
                'type' => '_PartialCollection',
                'id' => $this->getPageLink(),
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

    /**
     * @param UuidInterface[] $elementIds
     */
    public function buildElementCollectionFromIds(
        array $elementIds = [],
        int $totalElements = 0,
    ): CollectionResponse {
        $elementData = [];

        foreach ($elementIds as $elementId) {
            $element = $this->elementManager->getElement($elementId);
            if ($element) {
                $elementData[] = $this->elementToRawService->elementToRaw(
                    $element
                );
            }
        }

        $totalPages = $this->getTotalPages($totalElements);
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
                'type' => '_PartialElementCollection',
                'id' => $this->getPageLink(),
                'totalElements' => $totalElements,
                'links' => [
                    'first' => $this->getPageLink(1),
                    'previous' => $previousPageLink,
                    'next' => $nextPageLink,
                    'last' => $this->getPageLink($totalPages),
                ],
                'elements' => $elementData,
            ]
        );
    }
}
