<?php

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use InvalidArgumentException;

class NodeElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $nodeElement = $event->getNodeElement();

        $nodeElementIdentifier = $nodeElement->getIdentifier();
        if (null === $nodeElementIdentifier) {
            throw new InvalidArgumentException();
        }
        $nodeElementIdentifier = $nodeElementIdentifier->toString();

        $nodeLabel = $nodeElement->getLabel();
        if (null === $nodeLabel) {
            throw new InvalidArgumentException();
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
