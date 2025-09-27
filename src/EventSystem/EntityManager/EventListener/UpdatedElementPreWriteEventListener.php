<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPreMergeEvent;
use App\Service\AppStateService;
use App\Type\AppStateType;
use Safe\DateTime;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class UpdatedElementPreWriteEventListener
{
    public function __construct(
        private AppStateService $appStateService,
    ) {
    }

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
        if (AppStateType::LOADING_BACKUP === $this->appStateService->getAppState() && $element->hasProperty('updated')) {
            return;
        }
        $element->addProperty('updated', new DateTime());
    }
}
