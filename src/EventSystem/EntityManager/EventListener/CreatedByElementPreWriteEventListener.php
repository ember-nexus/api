<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPreCreateEvent;
use App\Security\AuthProvider;
use App\Service\AppStateService;
use App\Type\AppStateType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Sets the immutable `createdBy` property to the id of the acting user, unless the creating code already set it (e.g.
 * self-registration, token creation) or a backup is loaded: backups restore the stored value, and elements
 * from backups which predate the property stay without one.
 */
class CreatedByElementPreWriteEventListener
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
        if ($element->hasProperty('createdBy') || AppStateType::LOADING_BACKUP === $this->appStateService->getAppState()) {
            return;
        }
        $element->addProperty('createdBy', $this->authProvider->getUserId()->toString());
    }
}
