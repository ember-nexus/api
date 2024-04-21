<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class ResetElementPropertiesService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function resetElementProperties(
        NodeElementInterface|RelationElementInterface $element
    ): NodeElementInterface|RelationElementInterface {
        $elementPropertyResetEvent = new ElementPropertyResetEvent($element);
        $this->eventDispatcher->dispatch($elementPropertyResetEvent);

        $propertyNames = array_keys($element->getProperties());
        foreach ($propertyNames as $propertyName) {
            if (in_array($propertyName, $elementPropertyResetEvent->getPropertyNamesWhichAreKept())) {
                continue;
            }
            $element->addProperty($propertyName, null);
        }

        return $element;
    }
}
