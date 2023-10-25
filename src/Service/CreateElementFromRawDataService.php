<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Factory\Exception\Client400IncompleteMutualDependencyExceptionFactory;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Ramsey\Uuid\UuidInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CreateElementFromRawDataService
{
    public function __construct(
        private Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory,
        private Client400IncompleteMutualDependencyExceptionFactory $client400IncompleteMutualDependencyExceptionFactory,
        private ElementManager $elementManager,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function createElementFromRawData(
        UuidInterface $elementId,
        string $type,
        UuidInterface $startNodeId = null,
        UuidInterface $endNodeId = null,
        array $rawData = []
    ): NodeElementInterface|RelationElementInterface {
        if (null !== $startNodeId && null === $endNodeId) {
            throw $this->client400IncompleteMutualDependencyExceptionFactory->createFromTemplate(['start', 'end'], ['start'], ['end']);
        }
        if (null === $startNodeId && null !== $endNodeId) {
            throw $this->client400IncompleteMutualDependencyExceptionFactory->createFromTemplate(['start', 'end'], ['end'], ['start']);
        }
        if (null !== $this->elementManager->getElement($elementId)) {
            throw $this->client400ReservedIdentifierExceptionFactory->createFromTemplate($elementId->toString());
        }

        /**
         * @var array<string, mixed> $normalizedData
         */
        $normalizedData = [];
        foreach ($rawData as $rawPropertyName => $rawPropertyValue) {
            $rawValueToNormalizedValueEvent = new RawValueToNormalizedValueEvent($rawPropertyValue);
            $this->eventDispatcher->dispatch($rawValueToNormalizedValueEvent);
            $normalizedData[$rawPropertyName] = $rawValueToNormalizedValueEvent->getNormalizedValue();
        }

        $elementPropertyChangeEvent = new ElementPropertyChangeEvent($type, null, $normalizedData);
        $this->eventDispatcher->dispatch($elementPropertyChangeEvent);
        $verifiedData = $elementPropertyChangeEvent->getChangedProperties();

        if ($startNodeId && $endNodeId) {
            $element = new RelationElement();
            $element->setStart($startNodeId);
            $element->setEnd($endNodeId);
            $element->setType($type);
        } else {
            $element = new NodeElement();
            $element->setLabel($type);
        }
        $element->addProperties($verifiedData);
        $element->setIdentifier($elementId);

        return $element;
    }
}
