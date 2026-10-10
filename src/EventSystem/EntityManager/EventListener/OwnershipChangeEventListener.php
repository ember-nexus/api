<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Service\AppStateService;
use App\Service\QueueService;
use App\Type\AppStateType;
use App\Type\RabbitMQQueueType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * A node's `DETACH DELETE` is a single Cypher statement (see `vendor/syndesi/cypher-entity-manager`'s
 * `NodeDeleteToStatementEventListener`) that never dispatches `ElementPostDeleteEvent` for the relations it
 * cascades away, so a delete path that bypasses {@see \App\Service\DeletionService} never enqueues an
 * `ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE` message for the elements affected by an `OWNS`/`HAS_SEARCH_ACCESS`/
 * `IS_IN_GROUP` relation removed that way - their `_groupsWithSearchAccess`/`_usersWithSearchAccess` can go stale.
 */
class OwnershipChangeEventListener
{
    /**
     * Relation types which can change which groups/users have (direct or indirect) search access to an element.
     * `HAS_*_ACCESS` types other than search, `CREATED`, property-based rules and implicit rules are intentionally
     * excluded: they either don't feed search access at all, or enqueuing on them would risk an unbounded cascade.
     *
     * @var string[]
     */
    private array $relationshipTypesWhichCanTriggerOwnershipChange = [
        'OWNS',
        'HAS_SEARCH_ACCESS',
        'IS_IN_GROUP',
    ];

    public function __construct(
        private QueueService $queueService,
        private AppStateService $appStateService,
    ) {
    }

    #[AsEventListener]
    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        $this->handleEvent($event, 'create');
    }

    #[AsEventListener]
    public function onElementPostMerge(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event, 'merge');
    }

    #[AsEventListener]
    public function onElementPostDelete(ElementPostDeleteEvent $event): void
    {
        $this->handleEvent($event, 'delete');
    }

    private function handleEvent(
        ElementPostCreateEvent|ElementPostMergeEvent|ElementPostDeleteEvent $event,
        string $eventKind,
    ): void {
        if (AppStateType::LOADING_BACKUP === $this->appStateService->getAppState()) {
            // relations created/changed while a backup is only partially loaded would enqueue recalculation against
            // an incomplete graph; ElementUpdateAfterBackupLoadEvent already recalculates search access for every
            // element once the load has finished, so queuing anything here would be pointless (or wrong) meanwhile
            return;
        }
        $element = $event->getElement();
        if (!($element instanceof RelationElementInterface)) {
            return;
        }
        $type = $element->getType();
        if (null === $type) {
            return;
        }
        if (!in_array($type, $this->relationshipTypesWhichCanTriggerOwnershipChange, true)) {
            return;
        }
        $this->handleOwnershipChange($element, $eventKind);
    }

    public function handleOwnershipChange(RelationElementInterface $element, string $eventKind): void
    {
        $elementId = $element->getId();
        if (!$elementId) {
            return;
        }
        $this->queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            [
                'relationId' => $elementId->toString(),
                'relationType' => $element->getType(),
                'startId' => $element->getStart()?->toString(),
                'endId' => $element->getEnd()?->toString(),
                'eventKind' => $eventKind,
                // kept for parity with elements of the payload that describe the event itself; actual retry/drop
                // behavior is driven solely by QueueService::consumeQueue()'s AMQP header based try counter, so this
                // is not incremented or read anywhere, to avoid two separate/conflicting counters
                'tries' => 0,
            ]
        );
    }
}
