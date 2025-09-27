<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPreMergeEvent;
use Safe\DateTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class CreatedElementPreWriteEventListener
{
    #[AsEventListener]
    public function onElementPreCreateEvent(ElementPreCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onElementPreMergeEvent(ElementPreMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(ElementPreCreateEvent|ElementPreMergeEvent $event): void
    {
        $element = $event->getElement();
        if ($element->hasProperty('created')) {
            return;
        }
        $element->addProperty('created', new DateTime());
    }
}
