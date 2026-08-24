<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * The 'file' property is only ever set by the file upload/delete endpoints, never directly by a client. Allowing
 * direct writes would let a client forge file metadata (contentLength, hash, ...) for a file which does not
 * actually exist, or does not match what was actually uploaded.
 */
class FileElementPropertyChangeEventListener
{
    public function __construct(
        private Client400ForbiddenPropertyExceptionFactory $client400ForbiddenPropertyExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPropertyChangeEvent(ElementPropertyChangeEvent $event): void
    {
        if (!array_key_exists('file', $event->getChangedProperties())) {
            return;
        }
        throw $this->client400ForbiddenPropertyExceptionFactory->createFromTemplate('file');
    }
}
