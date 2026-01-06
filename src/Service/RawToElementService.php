<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;

class RawToElementService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
    ) {
    }

    /**
     * todo: make sure that $supportFile is never true in API calls. add feature tests.
     *
     * @param array<string, mixed> $rawData
     */
    public function rawToElement(array $rawData, bool $supportFile = false): NodeElementInterface|RelationElementInterface
    {
        if (!array_key_exists('type', $rawData)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'valid type');
        }
        $type = $rawData['type'];

        if (!array_key_exists('id', $rawData)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('id', 'valid UUID');
        }
        $id = Uuid::fromString($rawData['id']);

        $start = null;
        if (array_key_exists('start', $rawData)) {
            $start = Uuid::fromString($rawData['start']);
        }

        $end = null;
        if (array_key_exists('end', $rawData)) {
            $end = Uuid::fromString($rawData['end']);
        }

        $rawProperties = [];
        if (array_key_exists('data', $rawData)) {
            $rawProperties = $rawData['data'];
        }
        /**
         * @var array<string, mixed> $rawProperties
         */
        $normalizedProperties = [];
        foreach ($rawProperties as $rawPropertyName => $rawPropertyValue) {
            $rawValueToNormalizedValueEvent = new RawValueToNormalizedValueEvent($rawPropertyValue);
            $this->eventDispatcher->dispatch($rawValueToNormalizedValueEvent);
            $normalizedProperties[$rawPropertyName] = $rawValueToNormalizedValueEvent->getNormalizedValue();
        }

        if (true === $supportFile && array_key_exists('file', $rawData)) {
            $normalizedProperties['file'] = $rawData['file'];
        }

        if (null !== $start && null !== $end) {
            $element = new RelationElement();
            $element->setStart($start);
            $element->setEnd($end);
            $element->setType($type);
        } else {
            $element = new NodeElement();
            $element->setLabel($type);
        }
        $element->addProperties($normalizedProperties);
        $element->setId($id);

        return $element;
    }
}
