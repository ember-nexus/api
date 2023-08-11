<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class UserIdentifierPropertyElementFragmentizeEventListener
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
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
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        if ($element->hasProperty($uniqueIdentifier)) {
            $cypherFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
            $mongoFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
            $elasticFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
        }
    }
}
