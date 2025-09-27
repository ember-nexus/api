<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFragmentize\EventListener;

use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UserIdentifierPropertyElementFragmentizeEventListener
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
    ) {
    }

    #[AsEventListener]
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
