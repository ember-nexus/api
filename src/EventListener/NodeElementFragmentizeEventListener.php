<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;

class NodeElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $nodeElement = $event->getNodeElement();
        $event->getCypherFragment()
            ->addLabel($nodeElement->getLabel())
            ->addProperty('id', $nodeElement->getIdentifier()->toString())
            ->addIdentifier('id');
        $event->getDocumentFragment()
            ->setCollection($nodeElement->getLabel())
            ->setIdentifier($nodeElement->getIdentifier()->toString());
    }
}
