<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Service\AppStateService;
use App\Service\PendingSearchDocumentService;
use App\Service\QueueService;
use App\Type\AppStateType;
use App\Type\RabbitMQQueueType;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * A node's cascading `DETACH DELETE` never dispatches `ElementPostDeleteEvent` for the relations it removes, so a
 * delete path that doesn't delete each relation individually first would silently skip enqueuing ownership
 * recalculation for their endpoints - a latent hazard today, since every current delete path happens to delete
 * relations one by one before the node.
 */
class OwnershipChangeEventListener
{
    /**
     * Relation types which can change which groups/users have (direct or indirect) search access to an element.
     * `HAS_*_ACCESS` types other than search, property-based rules and implicit rules are intentionally
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
        private PendingSearchDocumentService $pendingSearchDocumentService,
    ) {
    }

    #[AsEventListener]
    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        if ($this->ownsNodeCreatedInSameFlush($event->getElement())) {
            // The new node has no descendants yet, apart from the ones which are created together with it and
            // calculate their own search access, and a new owner does not change the access of the owner itself.
            // Relations from the new node to existing elements are no OWNS-to-new-node case and still queue.
            return;
        }
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

    private function ownsNodeCreatedInSameFlush(NodeElementInterface|RelationElementInterface $element): bool
    {
        if (!($element instanceof RelationElementInterface) || 'OWNS' !== $element->getType()) {
            return false;
        }
        $endId = $element->getEnd();

        // during the post create events, the documents of everything created in this flush are still pending
        return null !== $endId && null !== $this->pendingSearchDocumentService->get($endId->toString());
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
