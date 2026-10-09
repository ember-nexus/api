<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Service\CronExecutionGateService;
use App\Service\CronTimeBudgetService;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Style\EmberNexusStyle;
use App\Type\RabbitMQQueueType;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:reindex-files', description: 'Updates element files within Elasticsearch. Restricted to files marked as updated.')]
class ReindexFilesCommand extends Command
{
    private EmberNexusStyle $io;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
        private CronTimeBudgetService $cronTimeBudgetService,
        private QueueService $queueService,
        private ElementManager $elementManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cron');

        if ($this->cronExecutionGateService->shouldSkipExecution()) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $deadline = $this->cronTimeBudgetService->getDeadline();
        if (null === $deadline) {
            $this->io->writeln('  Running interactively; no time limit applied.');
        }

        $this->io->writeln('  Reindexing files marked as updated...');

        $reindexedElementCount = $this->queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (array $eventData): void {
                $this->reindexElementFile($eventData);
            },
            $deadline
        );

        $this->io->newLine();
        $this->io->finalMessage(sprintf('Reindexed %d element file(s).', $reindexedElementCount));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function reindexElementFile(array $eventData): void
    {
        $rawElementId = $eventData['elementId'] ?? null;
        if (!is_string($rawElementId) || '' === $rawElementId) {
            throw new InvalidArgumentException(sprintf("Expected queue message to contain a string 'elementId', got %s.", get_debug_type($rawElementId)));
        }
        $elementId = Uuid::fromString($rawElementId);
        $element = $this->elementManager->getElement($elementId);
        if (null === $element) {
            // element was already deleted in the meantime, nothing left to reindex
            return;
        }

        // re-merging the element resyncs all of its properties, including its `file` metadata, into Elasticsearch
        $this->elementManager->merge($element);
        $this->elementManager->flush();

        $this->io->writeln(sprintf(
            '  Reindexed file of element <info>%s</info>.',
            $elementId->toString()
        ));
    }
}
