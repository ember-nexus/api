<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Syndesi\CypherDataStructures\Type\Node;

class RelationElementFragmentizeEventListener
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $relationElement = $event->getRelationElement();

        $relationUuid = $relationElement->getIdentifier();
        if (null === $relationUuid) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid UUID.');
        }
        $relationUuid = $relationUuid->toString();

        $relationType = $relationElement->getType();
        if (null === $relationType) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid type.');
        }

        $startUuid = $relationElement->getStart();
        if (null === $startUuid) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid start UUID.');
        }
        $startUuid = $startUuid->toString();

        $endUuid = $relationElement->getEnd();
        if (null === $endUuid) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid end UUID.');
        }
        $endUuid = $endUuid->toString();

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         *
         * @phpstan-ignore-next-line
         */
        $event->getCypherFragment()
            ->setType($relationElement->getType())
            ->setStartNode(
                (new Node())
                    ->addProperty('id', $startUuid)
                    ->addIdentifier('id')
            )
            ->setEndNode(
                (new Node())
                    ->addProperty('id', $endUuid)
                    ->addIdentifier('id')
            )
            ->addProperty('id', $relationUuid)
            ->addIdentifier('id');
        $event->getMongoFragment()
            ->setCollection($relationType)
            ->setIdentifier($relationUuid);
        $event->getElasticFragment()
            ->setIndex(sprintf(
                'relation_%s',
                strtolower($relationType)
            ))
            ->setIdentifier($relationUuid);
    }
}
