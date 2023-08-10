<?php

namespace App\EventListener;

use App\Event\NodeElementFragmentizeEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserIdentifierPropertyElementFragmentizeEventListener
{
    public function __construct(
        private ParameterBagInterface $bag
    ) {
    }

    public function onNodeElementFragmentizeEvent(NodeElementFragmentizeEvent $event): void
    {
        $element = $event->getNodeElement();
        if ('User' !== $element->getLabel()) {
            return;
        }

        $registerConfig = $this->bag->get('register');
        if (null === $registerConfig) {
            throw new \Exception("Unable to get unique identifier from config; key 'register' must exist.");
        }
        if (!is_array($registerConfig)) {
            throw new \Exception("Configuration key 'register' must be an array.");
        }
        $uniqueIdentifier = $registerConfig['uniqueIdentifier'];
        if (null === $uniqueIdentifier) {
            throw new \Exception("Unable to get unique identifier from config; key 'register.uniqueIdentifier' must exist.");
        }

        $cypherFragment = $event->getCypherFragment();
        $mongoFragment = $event->getMongoFragment();
        $elasticFragment = $event->getElasticFragment();
        if ($element->hasProperty($uniqueIdentifier)) {
            $cypherFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
            $mongoFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
            $elasticFragment->addProperty($uniqueIdentifier, $element->getProperty($uniqueIdentifier));
        }
    }
}
