<?php

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\Security\AccessChecker;
use App\Service\AppStateService;
use App\Type\AccessType;
use App\Type\AppStateType;
use Ramsey\Uuid\UuidInterface;
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
            $this->handleRelation($element);
        } else {
            $this->handleNode($element);
        }
    }

    private function handleNode(NodeElementInterface $element): void
    {
        $elementId = $element->getIdentifier();
        if (!$elementId) {
            return;
        }
        $groupsWithSearchAccess = $this->accessChecker->getDirectGroupsWithAccessToNode($elementId, AccessType::SEARCH);
        $usersWithSearchAccess = $this->accessChecker->getDirectUsersWithAccessToNode($elementId, AccessType::SEARCH);
        $groupsWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($groupsWithSearchAccess);
        $usersWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($usersWithSearchAccess);

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

    private function handleRelation(RelationElementInterface $element): void
    {
        $elementId = $element->getIdentifier();
        if (!$elementId) {
            return;
        }
        $groupsWithSearchAccess = $this->accessChecker->getDirectGroupsWithAccessToRelation($elementId, AccessType::SEARCH);
        $usersWithSearchAccess = $this->accessChecker->getDirectUsersWithAccessToRelation($elementId, AccessType::SEARCH);
        $groupsWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($groupsWithSearchAccess);
        $usersWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($usersWithSearchAccess);

        $document = new Document();
        $document
            ->setIdentifier($element->getIdentifier()?->toString())
            ->setIndex(sprintf(
                'relation_%s',
                strtolower($element->getType() ?? '')
            ))
            ->addProperties([
                '_groupsWithSearchAccess' => $groupsWithSearchAccess,
                '_usersWithSearchAccess' => $usersWithSearchAccess,
            ]);

        $this->elasticEntityManager->merge($document);
    }

    /**
     * @param UuidInterface[] $uuids
     *
     * @return string[]
     */
    private function convertArrayOfUuidsToArrayOfStrings(array $uuids): array
    {
        $output = [];
        foreach ($uuids as $uuid) {
            $output[] = $uuid->toString();
        }

        return $output;
    }
}
