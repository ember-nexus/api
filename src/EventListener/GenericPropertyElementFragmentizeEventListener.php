<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;

class GenericPropertyElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onRelationElementFragmentizeEvent(RelationElementFragmentizeEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(NodeElementFragmentizeEvent|RelationElementFragmentizeEvent $event): void
    {
        $cypherFragment = $event->getCypherFragment();
        $documentFragment = $event->getDocumentFragment();
        $element = null;
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        }
        if ($event instanceof RelationElementFragmentizeEvent) {
            $element = $event->getRelationElement();
        }
        foreach ($element->getProperties() as $name => $value) {
            if (is_array($value)) {
                $documentFragment->addProperty($name, $value);
                continue;
            }
            if (is_object($value)) {
                $documentFragment->addProperty($name, $value);
                continue;
            }
            if (is_numeric($value)) {
                $cypherFragment->addProperty($name, $value);
                continue;
            }
            if (is_bool($value)) {
                $cypherFragment->addProperty($name, $value);
                continue;
            }
            if (is_string($value)) {
                if (strlen($value) > 1024) {
                    $documentFragment->addProperty($name, $value);
                } else {
                    $cypherFragment->addProperty($name, $value);
                }
                continue;
            }
            throw new \Exception('unknown data type');
        }
    }
}
