<?php

declare(strict_types=1);

namespace App\Command\Cron;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\UploadService;
use App\Style\EmberNexusStyle;
use DateInterval;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use LogicException;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron:delete-expired-uploads', description: 'Deletes uploads which were not completed during their lifetime.')]
class DeleteExpiredUploadsCommand extends Command
{
    private EmberNexusStyle $io;

    public function __construct(
        private ParameterBagInterface $bag,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cron');

        $isCronDisabled = $this->bag->get('isCronDisabled');
        if (!is_bool($isCronDisabled)) {
            throw new LogicException(sprintf('Expected "isCronDisabled" to be of type boolean, got %s.', get_debug_type($isCronDisabled)));
        }
        if ($isCronDisabled) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        $expiredUploadIds = $this->getExpiredUploadIds();

        if (0 === count($expiredUploadIds)) {
            $this->io->writeln('  No expired uploads found.');
            $this->io->newLine();
            $this->io->finalMessage('Finished.');

            return Command::SUCCESS;
        }

        $this->io->writeln(sprintf(
            '  Found %d expired upload(s), deleting them...',
            count($expiredUploadIds)
        ));

        foreach ($expiredUploadIds as $expiredUploadId) {
            $this->deleteExpiredUpload($expiredUploadId);
        }

        $this->io->newLine();
        $this->io->finalMessage(sprintf('Deleted %d expired upload(s).', count($expiredUploadIds)));

        return Command::SUCCESS;
    }

    /**
     * @return string[]
     */
    private function getExpiredUploadIds(): array
    {
        $gracePeriodInSeconds = $this->emberNexusConfiguration->getFileExpiredUploadCanBeDeletedAfterExpirationInSeconds();
        $deletionThreshold = (new DateTime())->sub(new DateInterval(sprintf('PT%sS', $gracePeriodInSeconds)));

        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH (u:Upload) WHERE u.expires < $deletionThreshold RETURN u.id',
            [
                'deletionThreshold' => $deletionThreshold,
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
