<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\SearchStepInterface;
use App\Contract\SearchStepResultInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use App\Type\SearchStepResult;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class ElementHydrationStep implements SearchStepInterface
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementToRawService $elementToRawService,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function isDangerous(): bool
    {
        return false;
    }

    public function getIdentifier(): string
    {
        return 'elementHydration';
    }

    public function executeStep(array|string|null $query, array $parameters): SearchStepResultInterface
    {
        if (null !== $query) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('query', 'null', $query);
        }

        if (!array_key_exists('elementIds', $parameters)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('elementIds', 'array of element ids');
        }
        $elementIds = [];
        foreach ($parameters['elementIds'] as $rawElementId) {
            if ($rawElementId instanceof UuidInterface) {
                $elementIds[] = $rawElementId;
            } else {
                if (!is_string($rawElementId)) {
                    throw $this->client400BadContentExceptionFactory->createFromTemplate('elementId', 'string', $rawElementId);
                }
                $elementIds[] = Uuid::fromString($rawElementId);
            }
        }

        $elementData = [];
        foreach ($elementIds as $elementId) {
            $element = $this->elementManager->getElement($elementId);
            if ($element) {
                $elementData[] = $this->elementToRawService->elementToRaw(
                    $element
                );
            }
        }

        $searchStepResult = new SearchStepResult();
        $searchStepResult->setResults($elementData);
        $searchStepResult->setDebugData(
            $this->getIdentifier(),
            [
                'query' => $query,
                'parameters' => $parameters,
            ]
        );

        return $searchStepResult;
    }
}
