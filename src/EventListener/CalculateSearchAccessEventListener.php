<?php

namespace App\EventListener;

use App\Contract\RelationElementInterface;
use App\Event\ElementPostCreateEvent;
use App\Event\ElementUpdateAfterBackupLoadEvent;
use App\Security\AccessChecker;
use App\Service\AppStateService;
use App\Type\AccessType;
use App\Type\AppStateType;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class CalculateSearchAccessEventListener
{
    public function __construct(
        private AccessChecker $accessChecker,
        private AppStateService $appStateService,
        private ElasticEntityManager $elasticEntityManager
    ) {
    }

    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        if (AppStateType::LOADING_BACKUP === $this->appStateService->getAppState()) {
            // calculating search access on partial data does not make sense
            return;
        }
        $this->handleEvent($event);
    }

    public function onElementUpdateAfterBackupLoadEvent(ElementUpdateAfterBackupLoadEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function handleEvent(ElementPostCreateEvent|ElementUpdateAfterBackupLoadEvent $event): void
    {
        $element = $event->getElement();
        if ($element instanceof RelationElementInterface) {
            /**
             * @todo remove if condition; requires changing $this->accessChecker->getDirectGroupsWithAccessToElement() etc.
             */
            return;
        }
        $elementId = $element->getIdentifier();
        if (!$elementId) {
            return;
        }
        $groupsWithSearchAccess = $this->accessChecker->getDirectGroupsWithAccessToElement($elementId, AccessType::SEARCH);
        $usersWithSearchAccess = $this->accessChecker->getDirectUsersWithAccessToElement($elementId, AccessType::SEARCH);
        foreach ($groupsWithSearchAccess as $key => $value) {
            $groupsWithSearchAccess[$key] = $value->toString();
        }
        foreach ($usersWithSearchAccess as $key => $value) {
            $usersWithSearchAccess[$key] = $value->toString();
        }

        $document = new Document();
        $document
            ->setIdentifier($element->getIdentifier()?->toString())
            ->setIndex(sprintf(
                'node_%s',
                strtolower($element->getLabel() ?? '')
            ))
            ->addProperties([
                '_groupsWithSearchAccess' => $groupsWithSearchAccess,
                '_usersWithSearchAccess' => $usersWithSearchAccess,
            ]);

        $this->elasticEntityManager->merge($document);
    }
}
