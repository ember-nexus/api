<?php

declare(strict_types=1);

namespace App\EventSystem\ElementDefragmentize\EventListener;

use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Type\Uuid;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class RelationElementDefragmentizeEventListener
{
    public function __construct(
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onRelationElementDefragmentizeEvent(RelationElementDefragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();

        $identifier = $this->getUuidFromFragmentId($cypherFragment->getProperty('id'), 'requires cypher fragment to contain valid UUID.');

        $start = $cypherFragment->getStartNode();
        if (null === $start) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires cypher fragment to contain valid start node.');
        }
        $start = $this->getUuidFromFragmentId($start->getProperty('id'), 'requires start node to have valid UUID.');

        $end = $cypherFragment->getEndNode();
        if (null === $end) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element defragmentize event listener requires cypher fragment to contain valid end node.');
        }
        $end = $this->getUuidFromFragmentId($end->getProperty('id'), 'requires end node to have valid UUID.');

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $event->getRelationElement()
            ->setType($cypherFragment->getType())
            ->setId($identifier)
            ->setStart($start)
            ->setEnd($end);
    }

    private function getUuidFromFragmentId(mixed $rawId, string $requirement): Uuid
    {
        if (!is_string($rawId)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Relation element defragmentize event listener %s Got %s.', $requirement, get_debug_type($rawId)));
        }

        return Uuid::fromString($rawId);
    }
}
