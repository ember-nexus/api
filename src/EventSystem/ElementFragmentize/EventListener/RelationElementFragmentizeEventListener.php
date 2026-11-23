<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementTypeService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\CypherDataStructures\Type\Node;

class RelationElementFragmentizeEventListener
{
    public function __construct(
        private ElementTypeService $elementTypeService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $relationElement = $event->getRelationElement();

        $relationId = $relationElement->getId();
        if (null === $relationId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid UUID.');
        }
        $relationId = $relationId->toString();

        $relationType = $relationElement->getType();
        if (null === $relationType) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to contain valid type.');
        }

        $startId = $relationElement->getStart();
        if (null === $startId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid start UUID.');
        }
        $startUuid = $startId;
        $startId = $startId->toString();

        $endId = $relationElement->getEnd();
        if (null === $endId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Relation element fragmentize event listener requires relation to have valid end UUID.');
        }
        $endUuid = $endId;
        $endId = $endId->toString();

        // the labels let Neo4j find both nodes through their id index instead of scanning all nodes; an unknown node has
        // no label, the statement then matches nothing either way
        $startNode = (new Node())
            ->addProperty('id', $startId)
            ->addIdentifier('id');
        $startLabel = $this->elementTypeService->resolveNodeLabel($startUuid);
        if (null !== $startLabel) {
            $startNode->addLabel($startLabel);
        }
        $endNode = (new Node())
            ->addProperty('id', $endId)
            ->addIdentifier('id');
        $endLabel = $this->elementTypeService->resolveNodeLabel($endUuid);
        if (null !== $endLabel) {
            $endNode->addLabel($endLabel);
        }

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         */
        $event->getCypherFragment()
            ->setType($relationElement->getType())
            ->setStartNode($startNode)
            ->setEndNode($endNode)
            ->addProperty('id', $relationId)
            ->addIdentifier('id'); // @phpstan-ignore-line
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
