<?php

namespace App\Security;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\CheckUserSuppliedPropertiesEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class PropertyChecker
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * Checks different things, throws Exception if something is wrong.
     */
    public function runCheckUserSuppliedProperties(NodeElementInterface|RelationElementInterface $element, array $newProperties = []): void
    {
        $event = new CheckUserSuppliedPropertiesEvent($element, $newProperties);
        $this->eventDispatcher->dispatch($event);
    }
}
