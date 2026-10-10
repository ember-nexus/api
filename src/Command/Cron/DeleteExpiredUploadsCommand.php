<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Service\CronExecutionGateService;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Service\ExpiredUploadDeletionAttemptService;
use App\Service\UploadService;
use App\Style\EmberNexusStyle;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:delete-expired-uploads', description: 'Deletes uploads which were not completed during their lifetime.')]
class DeleteExpiredUploadsCommand extends Command
{
    private const int PAGE_SIZE = 100;

    private EmberNexusStyle $io;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private DeletionService $deletionService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private ExpiredUploadDeletionAttemptService $expiredUploadDeletionAttemptService,
        private LoggerInterface $logger,
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

        $deletedCount = 0;
        $failedCount = 0;
        $givenUpCount = 0;
        $deferredCount = 0;
        $lastUploadId = null;
        do {
            $expiredUploadIds = $this->getExpiredUploadIds($lastUploadId);
            foreach ($expiredUploadIds as $expiredUploadId) {
                $lastUploadId = $expiredUploadId;
                if ($this->expiredUploadDeletionAttemptService->isDeferred($expiredUploadId)) {
                    ++$deferredCount;
                    continue;
                }
                try {
                    $this->deleteExpiredUpload($expiredUploadId);
                    $this->expiredUploadDeletionAttemptService->clear($expiredUploadId);
                    ++$deletedCount;
                } catch (Throwable $throwable) {
                    if ($this->handleFailedDeletion($expiredUploadId, $throwable)) {
                        ++$givenUpCount;
                    } else {
                        ++$failedCount;
                    }
                }
            }
            // keyset pagination: the id strictly increases, so every upload is visited once and the loop always ends
        } while (count($expiredUploadIds) >= self::PAGE_SIZE);

        if (0 === $deletedCount + $failedCount + $givenUpCount + $deferredCount) {
            $this->io->writeln('  No expired uploads found.');
            $this->io->newLine();
            $this->io->finalMessage('Finished.');

            return Command::SUCCESS;
        }

        $this->io->newLine();
        $this->io->finalMessage(sprintf(
            'Deleted %d expired upload(s), %d failed (will be retried), %d given up, %d waiting for a retry.',
            $deletedCount,
            $failedCount,
            $givenUpCount,
            $deferredCount
        ));

        return Command::SUCCESS;
    }

    /**
     * @return bool true if the upload was given up
     */
    private function handleFailedDeletion(string $uploadId, Throwable $throwable): bool
    {
        $attempts = $this->expiredUploadDeletionAttemptService->recordFailure($uploadId);
        if ($attempts < ExpiredUploadDeletionAttemptService::MAX_ATTEMPTS) {
            $this->logger->error(sprintf(
                "Failed to delete expired upload '%s' (attempt %d of %d), will retry later: %s",
                $uploadId,
                $attempts,
                ExpiredUploadDeletionAttemptService::MAX_ATTEMPTS,
                $throwable->getMessage()
            ));
            $this->io->writeln(sprintf('  <error>Failed to delete expired upload %s</error>, will retry later.', $uploadId));

            return false;
        }

        // final attempt: remove at least the Upload node, so that it does not fail forever; its chunks (if any) are left
        // to a future scan for S3 objects without database counterpart
        try {
            $uploadElement = $this->elementManager->getElement(Uuid::fromString($uploadId));
            if (null !== $uploadElement) {
                $this->deletionService->delete($uploadElement);
            }
            $this->expiredUploadDeletionAttemptService->clear($uploadId);
            $outcome = 'The upload node was removed, chunks in S3 may be left behind.';
        } catch (Throwable $cleanupThrowable) {
            $outcome = sprintf('Removing the upload node failed as well: %s', $cleanupThrowable->getMessage());
        }
        $this->logger->error(sprintf(
            "Giving up deleting expired upload '%s' after %d failed attempts: %s %s",
            $uploadId,
            $attempts,
            $throwable->getMessage(),
            $outcome
        ));
        $this->io->writeln(sprintf('  <error>Gave up deleting expired upload %s.</error>', $uploadId));

        return true;
    }

    /**
     * @return string[]
     */
    private function getExpiredUploadIds(?string $afterUploadId): array
    {
        $gracePeriodInSeconds = $this->emberNexusConfiguration->getFileExpiredUploadCanBeDeletedAfterExpirationInSeconds();
        $deletionThreshold = (new DateTime())->sub(new DateInterval(sprintf('PT%sS', $gracePeriodInSeconds)));

        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH (u:Upload) WHERE u.expires < $deletionThreshold AND u.id IS NOT NULL AND ($afterUploadId IS NULL OR u.id > $afterUploadId) RETURN u.id ORDER BY u.id LIMIT $pageSize',
            [
                'deletionThreshold' => $deletionThreshold,
                'afterUploadId' => $afterUploadId,
                'pageSize' => self::PAGE_SIZE,
            ]
        ));

        $expiredUploadIds = [];
        foreach ($queryResult as $queryResultLine) {
            $expiredUploadId = $queryResultLine['u.id'];
            if (!is_string($expiredUploadId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property u.id as string, not %s.', get_debug_type($expiredUploadId))); // @codeCoverageIgnore
            }
            $expiredUploadIds[] = $expiredUploadId;
        }

        return $expiredUploadIds;
    }

    private function deleteExpiredUpload(string $uploadId): void
    {
        $uploadElement = $this->elementManager->getElement(Uuid::fromString($uploadId));
        if (null === $uploadElement) {
            // upload was already deleted in the meantime, nothing left to do
            return;
        }

        $upload = $this->uploadFactory->createUploadFromElement($uploadElement);

        $this->uploadService->deleteUploadAndChunks($upload);
        $this->elementManager->flush();

        $this->io->writeln(sprintf(
            '  Deleted expired upload <info>%s</info>.',
            $upload->getId()->toString()
        ));
    }
}
