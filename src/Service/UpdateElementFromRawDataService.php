<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class UpdateElementFromRawDataService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function updateElementFromRawData(
        NodeElementInterface|RelationElementInterface $element,
        array $rawData = []
    ): NodeElementInterface|RelationElementInterface {
        /**
         * @var array<string, mixed> $normalizedData
         */
        $normalizedData = [];
        foreach ($rawData as $rawPropertyName => $rawPropertyValue) {
            $rawValueToNormalizedValueEvent = new RawValueToNormalizedValueEvent($rawPropertyValue);
            $this->eventDispatcher->dispatch($rawValueToNormalizedValueEvent);
            $normalizedData[$rawPropertyName] = $rawValueToNormalizedValueEvent->getNormalizedValue();
        }

        if ($element instanceof NodeElementInterface) {
            $typeOrLabel = $element->getLabel();
        } else {
            /**
             * @var RelationElementInterface $element
             */
            $typeOrLabel = $element->getType();
        }
        if (null === $typeOrLabel) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Type or label should not be null here.');
        }

        $elementPropertyChangeEvent = new ElementPropertyChangeEvent($typeOrLabel, $element, $normalizedData);
        $this->eventDispatcher->dispatch($elementPropertyChangeEvent);
        $verifiedData = $elementPropertyChangeEvent->getChangedProperties();

        $element->addProperties($verifiedData);

        return $element;
    }
}
