<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Exception\InvalidUuidException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\Uuid;
use Psr\EventDispatcher\EventDispatcherInterface;

class RawToElementService
{
    /**
     * User supplied ids may carry historic timestamps, but not timestamps in the future; this tolerance covers clock
     * differences between client and server.
     */
    public const int FUTURE_TOLERANCE_MS = 60_000;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
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

        $id = $this->getIdFromRawData($rawData, $supportFile);
        $start = array_key_exists('start', $rawData) ? $this->getUuidFromRawData($rawData, 'start') : null;
        $end = array_key_exists('end', $rawData) ? $this->getUuidFromRawData($rawData, 'end') : null;
        $normalizedProperties = $this->normalizeProperties($rawData);

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
    private function getIdFromRawData(array $rawData, bool $supportFile): Uuid
    {
        if (!array_key_exists('id', $rawData)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('id', 'valid UUID');
        }
        $id = $this->getUuidFromRawData($rawData, 'id');
        if (!$supportFile && $id->getTimestampMs() > Uuid::nowMs() + self::FUTURE_TOLERANCE_MS) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Property 'id' must not contain a timestamp in the future, got %s.", $id->toString()));
        }

        return $id;
    }

    /**
     * @psalm-suppress MixedAssignment
     *
     * @param array<string, mixed> $rawData
     *
     * @return array<string, mixed>
     */
    private function normalizeProperties(array $rawData): array
    {
        $rawProperties = $rawData['data'] ?? [];
        if (!is_array($rawProperties)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('data', 'object', $rawProperties);
        }
        /** @var array<string, mixed> $rawProperties */
        $normalizedProperties = [];
        foreach ($rawProperties as $rawPropertyName => $rawPropertyValue) {
            $rawValueToNormalizedValueEvent = new RawValueToNormalizedValueEvent($rawPropertyValue);
            $this->eventDispatcher->dispatch($rawValueToNormalizedValueEvent);
            $normalizedProperties[$rawPropertyName] = $rawValueToNormalizedValueEvent->getNormalizedValue();
        }

        return $normalizedProperties;
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

    /**
     * @param array<string, mixed> $rawData
     */
    private function getUuidFromRawData(array $rawData, string $key): Uuid
    {
        $value = $this->getStringFromRawData($rawData, $key);
        try {
            return Uuid::fromString($value);
        } catch (InvalidUuidException $exception) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Property '%s' must be a valid UUID: %s", $key, $exception->getMessage()), $exception);
        }
    }
}
