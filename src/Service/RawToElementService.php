<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\RawToElementEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class RawToElementService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public function rawToElement(array $rawData): NodeElementInterface|RelationElementInterface
    {
        $event = new RawToElementEvent($rawData);
        $this->eventDispatcher->dispatch($event);

        $element = $event->getElement();
        if (null === $element) {
            throw new \LogicException('Unable to convert raw data to element');
        }

        return $element;
    }
}
