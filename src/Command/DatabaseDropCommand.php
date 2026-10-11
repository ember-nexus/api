<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use App\Type\RabbitMQQueueType;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Predis\Client;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsCommand(name: 'database:drop', description: 'Resets all connected databases.')]
class DatabaseDropCommand extends Command
{
    private const int MAX_S3_DELETE_ITERATIONS = 10000;

    private EmberNexusStyle $io;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private Client $redisClient,
        private AMQPStreamConnection $AMQPStreamConnection,
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NEGATABLE,
            'If enabled, command will not ask for manual confirmation.',
            false
        );
        $this->addOption(
            'no-files',
            null,
            InputOption::VALUE_NEGATABLE,
            'Disable deletion of object storage (S3) data, i.e. keep the storage and upload buckets untouched.',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Database Drop');

        if (!$input->getOption('force')) {
            /**
             * @var QuestionHelper $helper
             */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to drop all databases? [y/N]: ', false);
            if (!$helper->ask($input, $output, $question)) {
                $this->io->writeln('Aborted dropping databases.');

                return Command::FAILURE;
            }
            $this->io->newLine();
        }

        $this->deleteCypher();

        $this->deleteMongo();

        if ($input->getOption('no-files')) {
            $this->io->startSection('Task 3 of 6: Object Storage');
            $this->io->writeln('Skipping object storage deletion (--no-files).');
            $this->io->stopSection('Skipped object storage deletion.');
        } else {
            $this->deleteObjectStorage();
        }

        $this->deleteElastic();

        $this->deleteRedis();

        $this->deleteRabbitMQ();

        $this->io->finalMessage('Database successfully dropped.');

        return Command::SUCCESS;
    }

    private function deleteCypher(): void
    {
        $this->io->startSection('Task 1 of 6: Cypher');
        $this->io->writeln('Deleting Cypher data...');
        $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) DETACH DELETE n')
        );
        $this->io->stopSection('Successfully deleted cypher data.');
    }

    private function deleteMongo(): void
    {
        $this->io->startSection('Task 2 of 6: MongoDB');
        $this->io->writeln('Deleting Mongo data...');
        $mongoDatabase = $this->mongoEntityManager->getDatabase();
        if (null !== $mongoDatabase) {
            $this->mongoEntityManager->getClient()->dropDatabase($mongoDatabase);
        }
        $this->io->stopSection('Successfully deleted Mongo data.');
    }

    private function deleteObjectStorage(): void
    {
        $this->io->startSection('Task 3 of 6: Object Storage');
        $this->io->writeln('Deleting Object data...');
        $this->deleteAllObjectsFromBucket($this->emberNexusConfiguration->getFileS3StorageBucket());
        $this->deleteAllObjectsFromBucket($this->emberNexusConfiguration->getFileS3UploadBucket());
        $this->io->stopSection('Successfully deleted object storage.');
    }

    private function deleteAllObjectsFromBucket(string $bucket): void
    {
        $this->abortAllMultipartUploadsFromBucket($bucket);

        for ($iteration = 0; $iteration < self::MAX_S3_DELETE_ITERATIONS; ++$iteration) {
            $objects = $this->s3Client->listObjectsV2([
                'Bucket' => $bucket,
            ]);
            $objectsToBeDeleted = [];
            foreach ($objects->getContents() as $object) {
                $objectsToBeDeleted[] = [
                    'Key' => $object->getKey(),
                ];
            }
            if (0 === count($objectsToBeDeleted)) {
                return;
            }
            // S3 accepts at most 1000 keys per request
            foreach (array_chunk($objectsToBeDeleted, 1000) as $objectsChunk) {
                $result = $this->s3Client->deleteObjects([
                    'Bucket' => $bucket,
                    'Delete' => [
                        'Objects' => $objectsChunk,
                    ],
                ]);
                $errors = $result->getErrors();
                if (count($errors) > 0) {
                    $messages = [];
                    foreach ($errors as $error) {
                        $messages[] = sprintf('%s (%s)', $error->getKey() ?? '?', $error->getMessage() ?? $error->getCode() ?? 'unknown error');
                    }
                    throw new RuntimeException(sprintf("Unable to delete objects from bucket '%s': %s", $bucket, join(', ', $messages)));
                }
            }
        }

        throw new RuntimeException(sprintf("Bucket '%s' is not empty after %d delete rounds, aborting.", $bucket, self::MAX_S3_DELETE_ITERATIONS));
    }

    private function abortAllMultipartUploadsFromBucket(string $bucket): void
    {
        for ($iteration = 0; $iteration < self::MAX_S3_DELETE_ITERATIONS; ++$iteration) {
            $abortedUploads = 0;
            foreach ($this->s3Client->listMultipartUploads(['Bucket' => $bucket])->getUploads() as $upload) {
                $key = $upload->getKey();
                $uploadId = $upload->getUploadId();
                if (null === $key || null === $uploadId) {
                    continue;
                }
                $this->s3Client->abortMultipartUpload([
                    'Bucket' => $bucket,
                    'Key' => $key,
                    'UploadId' => $uploadId,
                ]);
                ++$abortedUploads;
            }
            if (0 === $abortedUploads) {
                return;
            }
        }

        throw new RuntimeException(sprintf("Unable to abort all unfinished multipart uploads of bucket '%s'.", $bucket));
    }

    private function deleteElastic(): void
    {
        $this->io->startSection('Task 4 of 6: Elastic Search');
        $this->io->writeln('Deleting Elastic data...');
        /**
         * @psalm-suppress PossiblyUndefinedMethod
         * @psalm-suppress InvalidArgument
         *
         * @phpstan-ignore-next-line method.notFound
         */
        $rawIndices = $this->elasticEntityManager->getClient()->cat()->indices(['index' => '*'])->asString();
        $rawIndices = explode("\n", $rawIndices);
        $indices = [];
        foreach ($rawIndices as $rawIndex) {
            $parts = explode(' ', $rawIndex);
            if (count($parts) >= 2) {
                $indices[] = $parts[2];
            }
        }
        $indices = array_unique($indices);
        sort($indices);
        foreach ($indices as $index) {
            try {
                $this->elasticEntityManager->getClient()->indices()->delete(['index' => $index]);
            } catch (Throwable $t) {
                $this->io->warning(sprintf(
                    "Unable to delete Elasticsearch index '%s'.\n%s",
                    $index,
                    $t->getMessage()
                ));
            }
        }
        $this->io->stopSection('Successfully deleted Elastic data.');
    }

    private function deleteRedis(): void
    {
        $this->io->startSection('Task 5 of 6: Redis');
        $this->io->writeln('Deleting Redis data...');
        $this->redisClient->flushdb();
        $this->io->stopSection('Successfully deleted Redis data.');
    }

    private function deleteRabbitMQ(): void
    {
        $this->io->startSection('Task 6 of 6: RabbitMQ');
        $this->io->writeln('Deleting RabbitMQ data...');
        $channel = $this->AMQPStreamConnection->channel();
        foreach (RabbitMQQueueType::cases() as $queue) {
            $channel->queue_delete($queue->value);
        }
        $channel->close();
        $this->io->stopSection('Successfully deleted RabbitMQ data.');
    }
}
