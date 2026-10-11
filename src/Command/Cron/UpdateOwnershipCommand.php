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
 * Recalculates `_groupsWithSearchAccess`/`_usersWithSearchAccess` for elements affected by relation changes; runs
 * asynchronously via queue, so search access can lag behind the graph by up to this command's cron interval. Each
 * message recalculates one element and queues its children instead of recursing in-process, bounding every message
 * to O(1) work.
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
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function handleOwnershipChangeMessage(array $eventData): void
    {
        $rawElementId = $eventData['elementId'] ?? null;
        if (is_string($rawElementId) && '' !== $rawElementId) {
            // queued directly by a parent's own recalculation, see recalculateElementAndQueueChildren()
            $this->recalculateElementAndQueueChildren(Uuid::fromString($rawElementId));

            return;
        }

        // the relation's own id and both endpoints can each be affected; since a deleted relation can't be reloaded,
        // all three ids travel in the payload itself. They are deduplicated since a self-referencing relation can
        // repeat an id across these fields.
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

        // IS_IN_GROUP grants the member the group's rights rather than access to the group node itself, so the
        // group's own delta is always zero and the normal cascade-on-change rule would never reach what actually
        // changed; its children are therefore always recalculated directly regardless of delta.
        $endId = $eventData['endId'] ?? null;
        if ('IS_IN_GROUP' === ($eventData['relationType'] ?? null) && is_string($endId) && '' !== $endId) {
            foreach ($this->getChildrenIds(Uuid::fromString($endId)) as $childId) {
                $this->recalculateElementAndQueueChildren($childId);
            }
        }
    }

    /**
     * Queues children only when this element's own access changed, since an unchanged parent means unchanged
     * children too; this also makes a cycle in the ownership graph self-terminating, as each message does O(1)
     * work and the cascade stops once nothing reachable changes anymore.
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
     * Reuses the same search-access calculation used on element create, so results stay consistent between
     * creation and recalculation.
     *
     * @return bool whether the stored access changed
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
