<?php

namespace App\EventListener;

use App\Event\ElementPreCreateEvent;
use App\Event\ElementPreMergeEvent;

class CreatedElementPreWriteEventListener
{
    public function onElementPreCreateEvent(ElementPreCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

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
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $element->addProperty('created', $now);
    }
}
