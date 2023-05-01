<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;

class UsernamePropertyElementFragmentizeEventListener
{
    public function __construct()
    {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $element = $event->getNodeElement();
        if ('User' !== $element->getLabel()) {
            return;
        }
        $cypherFragment = $event->getCypherFragment();
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();
        if ($element->hasProperty('username')) {
            $cypherFragment->addProperty('username', $element->getProperty('username'));
            $mongoFragment->addProperty('username', $element->getProperty('username'));
            $elasticFragment->addProperty('username', $element->getProperty('username'));
        }
    }
}
