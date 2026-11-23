<?php

declare(strict_types=1);

namespace App\EventSystem\SearchStep\EventListener;

use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\Exception\InvalidUuidException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use App\Service\ExpressionService;
use App\Type\SearchStepType;
use App\Type\Uuid;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
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

    private function parseUuid(mixed $rawId, string $propertyPath): Uuid
    {
        if ($rawId instanceof Uuid) {
            return $rawId;
        }
        if (!is_string($rawId)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($propertyPath, 'string', $rawId);
        }
        try {
            return Uuid::fromString($rawId);
        } catch (InvalidUuidException $exception) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($propertyPath, 'uuid (string)', $rawId, previous: $exception);
        }
    }

    /**
     * @return Uuid[]
     */
    private function getElementIdsFromElementsResult(mixed $stepResult): ?array
    {
        if (!is_array($stepResult) || !array_key_exists('elements', $stepResult)) {
            return null;
        }
        $elements = $stepResult['elements'];
        if (!is_array($elements)) {
            return null;
        }
        $elementIds = [];
        foreach ($elements as $i => $element) {
            if (!is_array($element) || !array_key_exists('id', $element)) {
                continue;
            }
            $elementIds[] = $this->parseUuid($element['id'], sprintf('elements[%d].id', (int) $i));
        }

        return $elementIds;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @return Uuid[]
     */
    private function getElementIdsFromPathsResult(mixed $stepResult): ?array
    {
        if (!is_array($stepResult) || !array_key_exists('paths', $stepResult)) {
            return null;
        }
        $paths = $stepResult['paths'];
        if (!is_array($paths)) {
            return null;
        }
        $elementIds = [];
        foreach ($paths as $i => $path) {
            if (!is_array($path) || !array_key_exists('nodeIds', $path) || !array_key_exists('relationIds', $path)) {
                continue;
            }
            foreach (['nodeIds', 'relationIds'] as $idType) {
                $rawIds = $path[$idType];
                if (!is_array($rawIds)) {
                    continue;
                }
                foreach ($rawIds as $j => $rawId) {
                    $elementIds[] = $this->parseUuid($rawId, sprintf('paths[%d].%s[%d]', (int) $i, $idType, (int) $j));
                }
            }
        }

        sort($elementIds);

        return $elementIds;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @return Uuid[]
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
            $elementIds[] = $this->parseUuid($rawElementId, sprintf('elementId[%d]', (int) $i));
        }

        return $elementIds;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @return Uuid[]
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
     * @return Uuid[]
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
                    'removedElementIds' => array_map(fn (Uuid $elementId) => $elementId->toString(), array_values(array_unique(array_diff_assoc($elementIds, $uniqueElementIds)))),
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
     * Adds the metadata of previous steps (e.g. Elasticsearch's score) as `metadata` after the native properties of the
     * element (e.g. `data` and `file`). The metadata of all
     * steps is merged into a single object in the order of the steps, so keys of later steps overwrite keys of earlier
     * steps.
     *
     * @param mixed[]                               $rawElement
     * @param array<int, array<string, mixed>>|null $stepMetadata
     *
     * @return mixed[]
     */
    private function addMetadataToRawElement(array $rawElement, ?array $stepMetadata): array
    {
        if (null === $stepMetadata) {
            return $rawElement;
        }
        ksort($stepMetadata);
        $metadata = array_merge([], ...array_values($stepMetadata));
        if (0 === count($metadata)) {
            return $rawElement;
        }

        $rawElement['metadata'] = $metadata;

        return $rawElement;
    }

    /**
     * @param Uuid[]                                          $elementIds
     * @param array<string, array<int, array<string, mixed>>> $elementMetadata metadata of previous steps, indexed by element id
     *
     * @return array<mixed[]>
     */
    private function getElementData(array $elementIds, array $elementMetadata = []): array
    {
        $elementData = [];
        if ([] === $elementIds) {
            return $elementData;
        }
        foreach ($this->elementManager->getElements($elementIds) as $element) {
            $elementData[] = $this->addMetadataToRawElement(
                $this->elementToRawService->elementToRaw($element),
                $elementMetadata[$element->getId()?->toString() ?? ''] ?? null
            );
        }

        return $elementData;
    }

    /**
     * @param Uuid[] $elementIds
     *
     * @return Uuid[]
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

    #[AsEventListener]
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

        $elementData = $this->getElementData($elementIds, $event->getPreviousElementMetadata());

        $event->setResults($elementData);

        $end = microtime(true);
        $event->addDebugData('end', $end);
        $event->addDebugData('duration', round($end - $start, 6));
        $event->stopPropagation();
    }
}
