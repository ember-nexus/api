<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\CypherDataStructures\Type\Node;

class RelationElementFragmentizeEventListener
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $relationElement = $event->getRelationElement();

        $relationId = $relationElement->getId();
        if (null === $relationId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid UUID.');
        }
        $relationId = $relationId->toString();

        $relationType = $relationElement->getType();
        if (null === $relationType) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid type.');
        }

        $startId = $relationElement->getStart();
        if (null === $startId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid start UUID.');
        }
        $startId = $startId->toString();

        $endId = $relationElement->getEnd();
        if (null === $endId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid end UUID.');
        }
        $endId = $endId->toString();

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         *
         * @phpstan-ignore-next-line
         */
        $event->getCypherFragment()
            ->setType($relationElement->getType())
            ->setStartNode(
                (new Node())
                    ->addProperty('id', $startId)
                    ->addIdentifier('id')
            )
            ->setEndNode(
                (new Node())
                    ->addProperty('id', $endId)
                    ->addIdentifier('id')
            )
            ->addProperty('id', $relationId)
            ->addIdentifier('id');
        $event->getMongoFragment()
            ->setCollection($relationType)
            ->setIdentifier($relationId);
        $event->getElasticFragment()
            ->setIndex(sprintf(
                'relation_%s',
                strtolower($relationType)
            ))
            ->setIdentifier($relationId);
    }
}
