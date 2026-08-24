<?php

declare(strict_types=1);

namespace App\EventSystem\ElementPropertyReset\EventListener;

use App\EventSystem\ElementPropertyReset\Event\ElementPropertyResetEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * A PUT (full data replace) must not wipe an element's associated file; files are managed through the dedicated
 * file/upload endpoints, independently of an element's 'data'.
 */
class FileElementPropertyResetEventListener
{
    #[AsEventListener]
    public function onElementPropertyResetEvent(ElementPropertyResetEvent $event): void
    {
        $event->addPropertyNameToBeKept('file');
    }
}
