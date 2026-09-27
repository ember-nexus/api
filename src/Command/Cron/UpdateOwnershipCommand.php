<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Service\CronExecutionGateService;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Service\SearchAccessCalculatorService;
use App\Style\EmberNexusStyle;
use App\Type\RabbitMQQueueType;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
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
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:update-ownership', description: 'Recalculates search access ownership of elements affected by relation changes.')]
class UpdateOwnershipCommand extends Command
{
    /**
     * Upper bound of elements recalculated (and, if changed, stored) per invocation, so a single cron tick can not
     * run unbounded if a large subtree changed. Remaining work is deferred to the next run (the still-queued
     * message is requeued via QueueService's own retry mechanism, see recalculateElementAndChildren()).
     */
    private const int MAX_ELEMENTS_PER_RUN = 500;

    private EmberNexusStyle $io;

    /**
     * @var array<string, true>
     */
    private array $visited = [];

    private int $updatedElementCount = 0;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
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
        $this->visited = [];
        $this->updatedElementCount = 0;

        $this->io->title('Cron');

        if ($this->cronExecutionGateService->shouldSkipExecution()) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $this->io->writeln('  Recalculating ownership search access...');

        $processedMessages = $this->queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            function (array $eventData): void {
                $this->handleOwnershipChangeMessage($eventData);
            }
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
        // the relation's own id (relations have search access documents too) plus both of its endpoints can each be
        // affected by the change; a deleted relation can no longer be reloaded, so all three ids travel in the
        // payload itself rather than being looked up from the (possibly already gone) relation
        foreach (['relationId', 'startId', 'endId'] as $key) {
            $rawId = $eventData[$key] ?? null;
            if (!is_string($rawId) || '' === $rawId) {
                continue;
            }
            $this->recalculateElementAndChildren(Uuid::fromString($rawId));
        }

        // IS_IN_GROUP is special: it does not change the group's (endId's) own direct accessors (it grants the
        // member the group's rights, not access to the group node itself), so the group's own delta above is always
        // zero and the normal "cascade to children only on non-zero delta" rule would never reach what actually
        // changed: every element the group (transitively) owns. So its children are always recalculated directly.
        $endId = $eventData['endId'] ?? null;
        if ('IS_IN_GROUP' === ($eventData['relationType'] ?? null) && is_string($endId) && '' !== $endId) {
            foreach ($this->getChildrenIds(Uuid::fromString($endId)) as $childId) {
                $this->recalculateElementAndChildren($childId);
            }
        }
    }

    /**
     * Work-list with a visited set (to cope with cycles in the ownership graph). Recomputes direct groups/users for
     * each affected element, stores only if the set changed, and only then enqueues that element's children for the
     * same recomputation: children are guaranteed unchanged when the parent's own delta was zero, so recursion stops
     * there.
     */
    private function recalculateElementAndChildren(UuidInterface $elementId): void
    {
        $workList = [$elementId->toString()];
        while ([] !== $workList) {
            $currentIdString = array_shift($workList);
            if (isset($this->visited[$currentIdString])) {
                continue;
            }
            if ($this->updatedElementCount >= self::MAX_ELEMENTS_PER_RUN) {
                // batch cap reached: throwing here (instead of just returning) makes QueueService requeue this
                // message via its normal try-count mechanism, so this remaining work is not silently dropped
                throw new RuntimeException(sprintf('Batch cap of %d element(s) per run reached; remaining work is deferred to the next cron run.', self::MAX_ELEMENTS_PER_RUN));
            }
            $this->visited[$currentIdString] = true;

            $currentId = Uuid::fromString($currentIdString);
            $element = $this->elementManager->getElement($currentId);
            if (null === $element) {
                // element was already deleted in the meantime, nothing left to recalculate
                continue;
            }

            if (!$this->recalculateSearchAccess($element)) {
                continue;
            }
            ++$this->updatedElementCount;

            foreach ($this->getChildrenIds($currentId) as $childId) {
                if (!isset($this->visited[$childId->toString()])) {
                    $workList[] = $childId->toString();
                }
            }
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
