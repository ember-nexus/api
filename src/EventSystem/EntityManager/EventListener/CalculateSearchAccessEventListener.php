<?php

declare(strict_types=1);

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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class CalculateSearchAccessEventListener
{
    public function __construct(
        private AccessChecker $accessChecker,
        private AppStateService $appStateService,
        private ElasticEntityManager $elasticEntityManager,
    ) {
    }

    #[AsEventListener]
    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        if (AppStateType::LOADING_BACKUP === $this->appStateService->getAppState()) {
            // calculating search access on partial data does not make sense
            return;
        }
        $this->handleEvent($event);
    }

    #[AsEventListener]
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
        $elementId = $element->getId();
        if (!$elementId) {
            return;
        }
        $groupsWithSearchAccess = $this->accessChecker->getDirectGroupsWithAccessToNode($elementId, AccessType::SEARCH);
        $usersWithSearchAccess = $this->accessChecker->getDirectUsersWithAccessToNode($elementId, AccessType::SEARCH);
        $groupsWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($groupsWithSearchAccess);
        $usersWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($usersWithSearchAccess);

        $document = new Document();
        $document
            ->setIdentifier($element->getId()?->toString())
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
        $elementId = $element->getId();
        if (!$elementId) {
            return;
        }
        $groupsWithSearchAccess = $this->accessChecker->getDirectGroupsWithAccessToRelation($elementId, AccessType::SEARCH);
        $usersWithSearchAccess = $this->accessChecker->getDirectUsersWithAccessToRelation($elementId, AccessType::SEARCH);
        $groupsWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($groupsWithSearchAccess);
        $usersWithSearchAccess = $this->convertArrayOfUuidsToArrayOfStrings($usersWithSearchAccess);

        $document = new Document();
        $document
            ->setIdentifier($element->getId()?->toString())
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
     * @param UuidInterface[] $ids
     *
     * @return string[]
     */
    private function convertArrayOfUuidsToArrayOfStrings(array $ids): array
    {
        $output = [];
        foreach ($ids as $id) {
            $output[] = $id->toString();
        }

        return $output;
    }
}
