<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class TokenElementPropertyChangeEventListener
{
    /**
     * Properties of a token which are only ever written by the application itself: the secret's hash, the lifecycle
     * (`state`, `expirationDate`) and the legacy names.
     */
    public const array PROTECTED_PROPERTIES = ['token', '_tokenHash', 'hash', 'state', 'expirationDate'];

    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if ('Token' !== $event->getLabelOrType()) {
            return;
        }
        foreach (self::PROTECTED_PROPERTIES as $propertyName) {
            if (array_key_exists($propertyName, $event->getChangedProperties())) {
                throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate($propertyName);
            }
        }
    }
}
