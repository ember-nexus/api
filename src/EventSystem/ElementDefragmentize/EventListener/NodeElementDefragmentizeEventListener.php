<?php

declare(strict_types=1);

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\Uuid;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class NodeElementDefragmentizeEventListener
{
    public function __construct(
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onNodeElementDefragmentizeEvent(NodeElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $identifier = $cypherFragment->getProperty('id');
        if (!is_string($identifier)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Node element defragmentize event listener requires cypher fragment to contain valid UUID, got %s.', get_debug_type($identifier)));
        }
        $event->getNodeElement()
            ->setLabel($cypherFragment->getLabels()[0])
            ->setId(Uuid::fromString($identifier));
    }
}
