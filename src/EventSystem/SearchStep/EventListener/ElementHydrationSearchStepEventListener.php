<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\EventListener;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use App\Service\ExpressionService;
use App\Type\SearchStepType;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class ElementHydrationSearchStepEventListener
{
    public const SearchStepType TYPE = SearchStepType::ELEMENT_HYDRATION;
    public const int NUMBER_MAX_ELEMENTS = 1000;

    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private AccessChecker $accessChecker,
        private AuthProvider $authProvider,
        private ExpressionService $expressionService,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    private function getPreviousSearchStepResults(SearchStepEvent $event): mixed
    {
        $parameters = $event->getParameters();
        if (!array_key_exists('stepResults', $parameters)) {
            return null;
        }
        $stepResults = $parameters['stepResults'];
        if (!is_array($stepResults)) {
            return null;
        }
        if (0 == count($stepResults)) {
            return null;
        }

        return $stepResults[count($stepResults) - 1];
    }

    /**
     * @return UuidInterface[]
     */
    private function getElementIdsFromElementsResult(mixed $stepResult): ?array
    {
        if (!array_key_exists('elements', $stepResult)) {
            return null;
        }
        $elements = $stepResult['elements'];
        if (!is_array($elements)) {
            return null;
        }
        $elementIds = [];
        foreach ($elements as $i => $element) {
            if (!array_key_exists('id', $element)) {
                continue;
            }
            $rawElementId = $element['id'];
            if ($rawElementId instanceof UuidInterface) {
                $elementIds[] = $rawElementId;
                continue;
            }
            if (!is_string($rawElementId)) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('elements[%d].id', $i), 'string', $rawElementId);
            }
            try {
                $elementIds[] = Uuid::fromString($rawElementId);
            } catch (InvalidUuidStringException $exception) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('elements[%d].id', $i), 'uuid (string)', $rawElementId, previous: $exception);
            }
        }

        return $elementIds;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     *
     * @return UuidInterface[]
     */
    private function getElementIdsFromPathsResult(mixed $stepResult): ?array
    {
        if (!array_key_exists('paths', $stepResult)) {
            return null;
        }
        $paths = $stepResult['paths'];
        if (!is_array($paths)) {
            return null;
        }
        $elementIds = [];
        foreach ($paths as $i => $path) {
            if (!array_key_exists('nodeIds', $path) || !array_key_exists('relationIds', $path)) {
                continue;
            }
            foreach ($path['nodeIds'] as $j => $rawNodeId) {
                if ($rawNodeId instanceof UuidInterface) {
                    $elementIds[] = $rawNodeId;
                    continue;
                }
                if (!is_string($rawNodeId)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('paths[%d].nodeIds[%d]', $i, $j), 'string', $rawNodeId);
                }
                try {
                    $elementIds[] = Uuid::fromString($rawNodeId);
                } catch (InvalidUuidStringException $exception) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('paths[%d].nodeIds[%d]', $i, $j), 'uuid (string)', $rawNodeId, previous: $exception);
                }
            }
            foreach ($path['relationIds'] as $j => $rawRelationId) {
                if ($rawRelationId instanceof UuidInterface) {
                    $elementIds[] = $rawRelationId;
                    continue;
                }
                if (!is_string($rawRelationId)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('paths[%d].relationIds[%d]', $i, $j), 'string', $rawRelationId);
                }
                try {
                    $elementIds[] = Uuid::fromString($rawRelationId);
                } catch (InvalidUuidStringException $exception) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('paths[%d].relationIds[%d]', $i, $j), 'uuid (string)', $rawRelationId, previous: $exception);
                }
            }
        }

        sort($elementIds);

        return $elementIds;
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     *
     * @return UuidInterface[]
     */
    private function getElementIdsFromQuery(SearchStepEvent $event): ?array
    {
        $query = $event->getQuery();
        if (null === $query) {
            return null;
        }
        if (!is_array($query)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'array', $query);
        }

        if (!array_key_exists('elementIds', $query)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('elementIds', 'array of element ids or expression yielding array of element ids');
        }
        $rawElementIds = $query['elementIds'];

        if (is_string($rawElementIds)) {
            if (str_starts_with($rawElementIds, '{{') && str_ends_with($rawElementIds, '}}')) {
                $expression = substr($rawElementIds, 2, -2);
                $rawElementIds = $this->expressionService->runExpression($expression, $event->getParameters());
            }
        }

        if (!is_array($rawElementIds)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('elementIds', 'array', $rawElementIds);
        }

        $elementIds = [];
        foreach ($rawElementIds as $i => $rawElementId) {
            if ($rawElementId instanceof UuidInterface) {
                $elementIds[] = $rawElementId;
            } else {
                if (!is_string($rawElementId)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('elementId[%d]', $i), 'string', $rawElementId);
                }
                try {
                    $elementIds[] = Uuid::fromString($rawElementId);
                } catch (InvalidUuidStringException $exception) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('elementId[%d]', $i), 'uuid (string)', $rawElementId, previous: $exception);
                }
            }
        }

        return $elementIds;
    }

    /**
     * @return UuidInterface[]
     */
    private function parseElementIdsFromEvent(SearchStepEvent $event): array
    {
        $elementIds = $this->getElementIdsFromQuery($event);
        if (null !== $elementIds) {
            return $elementIds;
        }
        $previousStepResult = $this->getPreviousSearchStepResults($event);
        if (null === $previousStepResult) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Expected either query to be string|array or previous step result to contain elements or paths.');
        }
        $elementIds = $this->getElementIdsFromElementsResult($previousStepResult);
        if (null !== $elementIds) {
            return $elementIds;
        }
        $elementIds = $this->getElementIdsFromPathsResult($previousStepResult);
        if (null !== $elementIds) {
            return $elementIds;
        }
        throw $this->client400BadContentExceptionFactory->createFromDetail('Expected either query to be string|array or previous step result to contain elements or paths.');
    }

    /**
     * @return UuidInterface[]
     */
    private function parseElementIds(SearchStepEvent $event): array
    {
        $elementIds = $this->parseElementIdsFromEvent($event);
        $uniqueElementIds = array_unique($elementIds);
        if (count($uniqueElementIds) !== count($elementIds)) {
            $event->addDebugData(
                sprintf('%s:redundantElementIds', self::TYPE->value),
                [
                    'message' => sprintf('Removed %d redundant elementIds.', count($elementIds) - count($uniqueElementIds)),
                    'removedElementIds' => array_map(fn (UuidInterface $elementId) => $elementId->toString(), array_values(array_unique(array_diff_assoc($elementIds, $uniqueElementIds)))),
                ]
            );
            $elementIds = array_values($uniqueElementIds);
        }

        if (count($elementIds) > self::NUMBER_MAX_ELEMENTS) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Number of elementIds to hydrate is %d, which exceeds the limit of %d; please limit the number of results returned by previous steps or parameters.', count($elementIds), self::NUMBER_MAX_ELEMENTS));
        }

        return $elementIds;
    }

    /**
     * @param UuidInterface[] $elementIds
     *
     * @return array<mixed[]>
     */
    private function getElementData(array $elementIds): array
    {
        $elementData = [];
        foreach ($elementIds as $elementId) {
            $element = $this->elementManager->getElement($elementId);
            if ($element) {
                $elementData[] = $this->elementToRawService->elementToRaw(
                    $element
                );
            }
        }

        return $elementData;
    }

    /**
     * @param UuidInterface[] $elementIds
     *
     * @return UuidInterface[]
     */
    private function filterElementIdsToAccessibleOnly(array $elementIds, SearchStepEvent $event): array
    {
        $filteredElementIds = $this->accessChecker->checkUserAccessToMultipleElements($this->authProvider->getUserId(), $elementIds);

        if (count($filteredElementIds) !== count($elementIds)) {
            $event->addDebugData(
                sprintf('%s:accessibleElementIds', self::class),
                [
                    'message' => 'Removed elementIds due to missing access rights.',
                ]
            );
        }

        return $filteredElementIds;
    }

    public function onSearchStepEvent(SearchStepEvent $event): void
    {
        if (self::TYPE !== $event->getType()) {
            return;
        }

        $event->addDebugData('step-handler', sprintf('%s (%s)', self::TYPE->value, self::class));

        $start = microtime(true);
        $event->addDebugData('start', $start);

        $elementIds = $this->parseElementIds($event);
        $elementIds = $this->filterElementIdsToAccessibleOnly($elementIds, $event);

        $event->addDebugData('accessibleElementIds', $elementIds);

        $elementData = $this->getElementData($elementIds);

        $event->setResults($elementData);

        $end = microtime(true);
        $event->addDebugData('end', $end);
        $event->addDebugData('duration', round($end - $start, 6));
        $event->stopPropagation();
    }
}
