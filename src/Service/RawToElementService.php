<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Factory\Exception\Client400BadContentExceptionFactory;
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
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param array<string, mixed> $rawData
     */
    public function rawToElement(array $rawData, bool $supportFile = false): NodeElementInterface|RelationElementInterface
    {
        if (!array_key_exists('type', $rawData)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'valid type');
        }
        $type = $rawData['type'];
        if (!is_string($type)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'string', $type);
        }

        if (!array_key_exists('id', $rawData)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('id', 'valid UUID');
        }
        $id = Uuid::fromString($this->getStringFromRawData($rawData, 'id'));

        $start = null;
        if (array_key_exists('start', $rawData)) {
            $start = Uuid::fromString($this->getStringFromRawData($rawData, 'start'));
        }

        $end = null;
        if (array_key_exists('end', $rawData)) {
            $end = Uuid::fromString($this->getStringFromRawData($rawData, 'end'));
        }

        $rawProperties = [];
        if (array_key_exists('data', $rawData)) {
            if (!is_array($rawData['data'])) {
                throw $this->client400BadContentExceptionFactory->createFromTemplate('data', 'object', $rawData['data']);
            }
            /** @var array<string, mixed> $rawProperties */
            $rawProperties = $rawData['data'];
        }
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

    /**
     * @param array<string, mixed> $rawData
     */
    private function getStringFromRawData(array $rawData, string $key): string
    {
        $value = $rawData[$key];
        if (!is_string($value)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate($key, 'string', $value);
        }

        return $value;
    }
}
