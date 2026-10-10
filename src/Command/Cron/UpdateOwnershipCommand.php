<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Service\CronExecutionGateService;
use App\Service\CronTimeBudgetService;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Service\SearchAccessCalculatorService;
use App\Style\EmberNexusStyle;
use App\Type\RabbitMQQueueType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

/**
 * Recalculates `_groupsWithSearchAccess`/`_usersWithSearchAccess` for elements affected by relation changes
 * (`OWNS`/`HAS_SEARCH_ACCESS`/`IS_IN_GROUP`) published by {@see \App\EventSystem\EntityManager\EventListener\OwnershipChangeEventListener}.
 *
 * Recalculation only happens once this command drains the queue, so a stored element's search access can lag behind
 * the graph by up to the interval this command is scheduled at (i.e. an eventual-consistency window).
 *
 * Each queue message recalculates exactly one element: either a relation's own id/endpoints (for the original
 * relation-change event), or a single `elementId` (for a child, queued directly by the parent that affected it, see
 * {@see recalculateElementAndQueueChildren()}). Children are never walked in-process - this bounds every message to
 * O(1) work and lets a deep subtree change settle over several cron ticks instead of one unbounded run.
 *
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:update-ownership', description: 'Recalculates search access ownership of elements affected by relation changes.')]
class UpdateOwnershipCommand extends Command
{
    private EmberNexusStyle $io;

    private int $updatedElementCount = 0;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
        private CronTimeBudgetService $cronTimeBudgetService,
        private QueueService $queueService,
        private ElementManager $elementManager,
        private SearchAccessCalculatorService $searchAccessCalculatorService,
        private ElasticEntityManager $elasticEntityManager,
        private CypherEntityManager $cypherEntityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);
        $this->updatedElementCount = 0;

        $this->io->title('Cron');

        if ($this->cronExecutionGateService->shouldSkipExecution()) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $deadline = $this->cronTimeBudgetService->getDeadline();
        if (null === $deadline) {
            $this->io->writeln('  Running interactively; no time limit applied.');
        }

        $this->io->writeln('  Recalculating ownership search access...');

        $processedMessages = $this->queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            function (array $eventData): void {
                $this->handleOwnershipChangeMessage($eventData);
            },
            $deadline
        );

        $this->io->newLine();
        $this->io->finalMessage(sprintf(
            'Processed %d queue message(s), updated search access of %d element(s).',
            $processedMessages,
            $this->updatedElementCount
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function handleOwnershipChangeMessage(array $eventData): void
    {
        $rawElementId = $eventData['elementId'] ?? null;
        if (is_string($rawElementId) && '' !== $rawElementId) {
            // queued directly by a parent's own recalculation, see recalculateElementAndQueueChildren()
            $this->recalculateElementAndQueueChildren(Uuid::fromString($rawElementId));

            return;
        }

        // the original relation-change event: the relation's own id (relations have search access documents too)
        // plus both of its endpoints can each be affected by the change; a deleted relation can no longer be
        // reloaded, so all three ids travel in the payload itself rather than being looked up from the (possibly
        // already gone) relation. They are deduplicated since e.g. a self-referencing relation can have the same
        // id in more than one of these three fields, and each distinct element only needs recalculating once.
        $rawIds = [];
        foreach (['relationId', 'startId', 'endId'] as $key) {
            $rawId = $eventData[$key] ?? null;
            if (is_string($rawId) && '' !== $rawId) {
                $rawIds[$rawId] = true;
            }
        }
        foreach (array_keys($rawIds) as $rawId) {
            $this->recalculateElementAndQueueChildren(Uuid::fromString($rawId));
        }

        // IS_IN_GROUP is special: it does not change the group's (endId's) own direct accessors (it grants the
        // member the group's rights, not access to the group node itself), so the group's own delta above is always
        // zero and the normal "cascade to children only on non-zero delta" rule would never reach what actually
        // changed: every element the group (transitively) owns. So its children are always recalculated directly.
        $endId = $eventData['endId'] ?? null;
        if ('IS_IN_GROUP' === ($eventData['relationType'] ?? null) && is_string($endId) && '' !== $endId) {
            foreach ($this->getChildrenIds(Uuid::fromString($endId)) as $childId) {
                $this->recalculateElementAndQueueChildren($childId);
            }
        }
    }

    /**
     * Recomputes direct groups/users for the given element, stores only if the set changed, and only then queues
     * that element's children for the same recomputation as separate messages: children are guaranteed unchanged
     * when the parent's own delta was zero, so the cascade stops there. Queueing (instead of recursing in-process)
     * also means a cycle in the ownership graph can not loop forever here - each message does O(1) work, and the
     * cascade self-terminates once recalculation everywhere it reaches reports no further change.
     */
    private function recalculateElementAndQueueChildren(UuidInterface $elementId): void
    {
        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            // element was already deleted in the meantime, nothing left to recalculate
            return;
        }

        if (!$this->recalculateSearchAccess($element)) {
            return;
        }
        ++$this->updatedElementCount;

        foreach ($this->getChildrenIds($elementId) as $childId) {
            $this->queueService->publishEvent(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE, [
                'elementId' => $childId->toString(),
            ]);
        }
    }

    /**
     * Recomputes the direct groups/users with search access for the given element (reusing the exact same
     * calculation {@see \App\EventSystem\EntityManager\EventListener\CalculateSearchAccessEventListener} uses on
     * create), compares it against the currently stored Elasticsearch document as sets, and stores it only if it
     * changed. Returns whether it changed.
     */
    private function recalculateSearchAccess(NodeElementInterface|RelationElementInterface $element): bool
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            return false;
        }

        $index = $this->searchAccessCalculatorService->getIndexForElement($element);
        $access = $this->searchAccessCalculatorService->calculateSearchAccess($element);

        $existingDocument = $this->elasticEntityManager->getOneByIdentifier($index, $elementId->toString());
        $existingGroups = $this->toStringArray($existingDocument?->getProperty('_groupsWithSearchAccess'));
        $existingUsers = $this->toStringArray($existingDocument?->getProperty('_usersWithSearchAccess'));

        if ($this->sameSet($existingGroups, $access['groups']) && $this->sameSet($existingUsers, $access['users'])) {
            return false;
        }

        $document = new Document();
        $document
            ->setIdentifier($elementId->toString())
            ->setIndex($index)
            ->addProperties([
                '_groupsWithSearchAccess' => $access['groups'],
                '_usersWithSearchAccess' => $access['users'],
            ]);
        $this->elasticEntityManager->merge($document);
        $this->elasticEntityManager->flush();

        $this->io->writeln(sprintf('  Updated search access of element <info>%s</info>.', $elementId->toString()));

        return true;
    }

    /**
     * @return UuidInterface[]
     */
    private function getChildrenIds(UuidInterface $parentId): array
    {
        // same "children of an element" graph semantics as EtagCalculatorService::calculateChildrenCollectionEtag():
        // nodes/relations directly OWNed by the element
        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (parent {id: \$parentId})-[:OWNS]->(children)\n".
            'RETURN children.id AS id',
            ['parentId' => $parentId->toString()]
        ));

        $ids = [];
        foreach ($result as $row) {
            $rawId = $row['id'];
            if (!is_string($rawId)) {
                continue;
            }
            $ids[] = Uuid::fromString($rawId);
        }

        return $ids;
    }

    /**
     * @return string[]
     */
    private function toStringArray(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, 'is_string'));
    }

    /**
     * @param string[] $a
     * @param string[] $b
     */
    private function sameSet(array $a, array $b): bool
    {
        sort($a);
        sort($b);

        return $a === $b;
    }
}
