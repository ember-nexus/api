<?php

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;

class NodeElementFragmentizeEventListener
{
    public function __construct(
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $nodeElement = $event->getNodeElement();

        $nodeElementIdentifier = $nodeElement->getIdentifier();
        if (null === $nodeElementIdentifier) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Node element fragmentize event listener requires node to contain valid UUID.');
        }
        $nodeElementIdentifier = $nodeElementIdentifier->toString();

        $nodeLabel = $nodeElement->getLabel();
        if (null === $nodeLabel) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Node element fragmentize event listener requires node to contain valid label.');
        }

        /**
         * @psalm-suppress UndefinedInterfaceMethod
         *
         * @phpstan-ignore-next-line
         */
        $event->getCypherFragment()
            ->addLabel($nodeLabel)
            ->addProperty('id', $nodeElementIdentifier)
            ->addIdentifier('id');
        $event->getMongoFragment()
            ->setCollection($nodeLabel)
            ->setIdentifier($nodeElementIdentifier);
        $event->getElasticFragment()
            ->setIndex(sprintf(
                'node_%s',
                strtolower($nodeLabel)
            ))
            ->setIdentifier($nodeElementIdentifier);
    }
}
