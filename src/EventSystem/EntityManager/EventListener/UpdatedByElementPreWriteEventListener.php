<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPreMergeEvent;
use App\Security\AuthProvider;
use App\Service\AppStateService;
use App\Type\AppStateType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Sets the immutable-by-clients `updatedBy` property to the id of the acting user on every write. On creation, a value
 * set by the creating code is kept; while loading a backup, the stored value is kept.
 */
class UpdatedByElementPreWriteEventListener
{
    public function __construct(
        private AuthProvider $authProvider,
        private AppStateService $appStateService,
    ) {
    }

    #[AsEventListener]
    public function onElementPreCreateEvent(ElementPreCreateEvent $event): void
    {
        $element = $event->getElement();
        if ($element->hasProperty('updatedBy') || AppStateType::LOADING_BACKUP === $this->appStateService->getAppState()) {
            return;
        }
        $element->addProperty('updatedBy', $this->authProvider->getUserId()->toString());
    }

    #[AsEventListener]
    public function onElementPreMergeEvent(ElementPreMergeEvent $event): void
    {
        $element = $event->getElement();
        if (AppStateType::LOADING_BACKUP === $this->appStateService->getAppState() && $element->hasProperty('updatedBy')) {
            return;
        }
        $element->addProperty('updatedBy', $this->authProvider->getUserId()->toString());
    }
}
