<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\Service\AppStateService;
use App\Service\SearchAccessCalculatorService;
use App\Type\AppStateType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

class CalculateSearchAccessEventListener
{
    public function __construct(
        private AppStateService $appStateService,
        private ElasticEntityManager $elasticEntityManager,
        private SearchAccessCalculatorService $searchAccessCalculatorService,
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
        $elementId = $element->getId();
        if (!$elementId) {
            return;
        }

        $access = $this->searchAccessCalculatorService->calculateSearchAccess($element);

        $document = new Document();
        $document
            ->setIdentifier($elementId->toString())
            ->setIndex($this->searchAccessCalculatorService->getIndexForElement($element))
            ->addProperties([
                '_groupsWithSearchAccess' => $access['groups'],
                '_usersWithSearchAccess' => $access['users'],
            ]);

        $this->elasticEntityManager->merge($document);
    }
}
