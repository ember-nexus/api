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
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();
        $element = null;
        if ($event instanceof NodeElementFragmentizeEvent) {
            $element = $event->getNodeElement();
        }
        if ($event instanceof RelationElementFragmentizeEvent) {
            $element = $event->getRelationElement();
        }
        foreach ($element->getProperties() as $name => $value) {
            $elasticFragment->addProperty($name, $value);
            if (is_array($value)) {
                $mongoFragment->addProperty($name, $value);
                continue;
            }
            if (is_object($value)) {
                $mongoFragment->addProperty($name, $value);
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
                    $mongoFragment->addProperty($name, $value);
                } else {
                    $cypherFragment->addProperty($name, $value);
                }
                continue;
            }
            if (is_null($value)) {
                // todo null on level 0 -> delete key-value-pair?
                $mongoFragment->addProperty($name, $value);
                $cypherFragment->addProperty($name, $value);
                $elasticFragment->addProperty($name, $value);
                continue;
            }
            throw new \Exception('unknown data type');
        }
    }
}
