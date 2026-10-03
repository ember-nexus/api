<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\DatabaseDropCommand;
use App\Type\RabbitMQQueueType;
use AsyncAws\S3\Result\DeleteObjectsOutput;
use AsyncAws\S3\Result\ListMultipartUploadsOutput;
use AsyncAws\S3\Result\ListObjectsV2Output;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\AwsObject;
use AsyncAws\S3\ValueObject\Error;
use AsyncAws\S3\ValueObject\MultipartUpload;
use Elastic\Elasticsearch\ClientBuilder;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[Small]
#[CoversClass(DatabaseDropCommand::class)]
class DatabaseDropCommandTest extends TestCase
{
    use ProphecyTrait;

    private const string STORAGE_BUCKET = 'storage-bucket';
    private const string UPLOAD_BUCKET = 'upload-bucket';

    private ObjectProphecy $s3Client;
    private ObjectProphecy $redisClient;
    private ObjectProphecy $amqpChannel;

    protected function setUp(): void
    {
        $this->s3Client = $this->prophesize(S3Client::class);
        $this->redisClient = $this->prophesize(RedisClient::class);
        $this->amqpChannel = $this->prophesize(AMQPChannel::class);
    }

    private function buildCommandTester(bool $isRunCompleted = true): CommandTester
    {
        $null = null;
        $neo4jClient = $this->prophesize(ClientInterface::class);
        $neo4jClient->runStatement(Argument::any())->shouldBeCalledOnce()->willReturn(new SummarizedResult($null, []));
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($neo4jClient->reveal());

        $mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $mongoEntityManager->getDatabase()->willReturn(null);

        // the client is final, so a real one answers the request for the index list with an empty list
        $elasticClient = ClientBuilder::create()
            ->setHttpClient(new GuzzleClient(['handler' => HandlerStack::create(new MockHandler([
                new Response(200, ['X-Elastic-Product' => 'Elasticsearch'], ''),
            ]))]))
            ->build();
        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getClient()->willReturn($elasticClient);

        // the databases after the object storage are only reached if the storage could be emptied
        $expectedCalls = $isRunCompleted ? 1 : 0;
        $this->redisClient->flushdb()->shouldBeCalledTimes($expectedCalls);

        foreach (RabbitMQQueueType::cases() as $queue) {
            $this->amqpChannel->queue_delete($queue->value)->shouldBeCalledTimes($expectedCalls);
        }
        $this->amqpChannel->close()->shouldBeCalledTimes($expectedCalls);
        $amqpConnection = $this->prophesize(AMQPStreamConnection::class);
        $amqpConnection->channel()->willReturn($this->amqpChannel->reveal());

        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileS3StorageBucket()->willReturn(self::STORAGE_BUCKET);
        $configuration->getFileS3UploadBucket()->willReturn(self::UPLOAD_BUCKET);

        $command = new DatabaseDropCommand(
            $cypherEntityManager->reveal(),
            $mongoEntityManager->reveal(),
            $elasticEntityManager->reveal(),
            $this->redisClient->reveal(),
            $amqpConnection->reveal(),
            $this->s3Client->reveal(),
            $configuration->reveal(),
        );
        $application = new Application();
        $application->addCommand($command);

        return new CommandTester($command);
    }

    /**
     * @param string[] $keys
     */
    private function listOutput(array $keys): ListObjectsV2Output
    {
        $output = $this->prophesize(ListObjectsV2Output::class);
        $output->getContents()->willReturn(array_map(static fn (string $key) => new AwsObject(['Key' => $key]), $keys));

        return $output->reveal();
    }

    /**
     * @param array<int, array{0: ?string, 1: ?string}> $uploads pairs of key and upload id
     */
    private function multipartOutput(array $uploads): ListMultipartUploadsOutput
    {
        $output = $this->prophesize(ListMultipartUploadsOutput::class);
        $output->getUploads()->willReturn(array_map(static fn (array $upload) => new MultipartUpload(['Key' => $upload[0], 'UploadId' => $upload[1]]), $uploads));

        return $output->reveal();
    }

    /**
     * @param Error[] $errors
     */
    private function deleteOutput(array $errors = []): DeleteObjectsOutput
    {
        $output = $this->prophesize(DeleteObjectsOutput::class);
        $output->getErrors()->willReturn($errors);

        return $output->reveal();
    }

    private function noMultipartUploads(string $bucket): void
    {
        $this->s3Client->listMultipartUploads(['Bucket' => $bucket])->willReturn($this->multipartOutput([]));
    }

    public function testDropsAllDatabasesAndEmptiesBothBuckets(): void
    {
        $this->noMultipartUploads(self::STORAGE_BUCKET);
        $this->noMultipartUploads(self::UPLOAD_BUCKET);
        $this->s3Client->listObjectsV2(['Bucket' => self::STORAGE_BUCKET])->willReturn($this->listOutput(['a', 'b']), $this->listOutput([]));
        $this->s3Client->listObjectsV2(['Bucket' => self::UPLOAD_BUCKET])->willReturn($this->listOutput(['c']), $this->listOutput([]));
        $this->s3Client->deleteObjects([
            'Bucket' => self::STORAGE_BUCKET,
            'Delete' => ['Objects' => [['Key' => 'a'], ['Key' => 'b']]],
        ])->shouldBeCalledOnce()->willReturn($this->deleteOutput());
        $this->s3Client->deleteObjects([
            'Bucket' => self::UPLOAD_BUCKET,
            'Delete' => ['Objects' => [['Key' => 'c']]],
        ])->shouldBeCalledOnce()->willReturn($this->deleteOutput());

        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(['--force' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Successfully deleted object storage.', $tester->getDisplay());
        $this->assertStringContainsString('Database successfully dropped.', $tester->getDisplay());
    }

    public function testNoFilesOptionLeavesObjectStorageUntouched(): void
    {
        $this->s3Client->listObjectsV2(Argument::cetera())->shouldNotBeCalled();
        $this->s3Client->listMultipartUploads(Argument::cetera())->shouldNotBeCalled();
        $this->s3Client->deleteObjects(Argument::cetera())->shouldNotBeCalled();

        $tester = $this->buildCommandTester();
        $exitCode = $tester->execute(['--force' => true, '--no-files' => true]);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Skipping object storage deletion (--no-files).', $tester->getDisplay());
    }

    public function testAbortsUnfinishedMultipartUploadsBeforeDeletingObjects(): void
    {
        $this->s3Client->listMultipartUploads(['Bucket' => self::STORAGE_BUCKET])->willReturn(
            $this->multipartOutput([['key-1', 'upload-1'], ['key-2', 'upload-2'], [null, 'no-key'], ['no-upload-id', null]]),
            $this->multipartOutput([])
        );
        $this->noMultipartUploads(self::UPLOAD_BUCKET);
        $this->s3Client->abortMultipartUpload(['Bucket' => self::STORAGE_BUCKET, 'Key' => 'key-1', 'UploadId' => 'upload-1'])->shouldBeCalledOnce();
        $this->s3Client->abortMultipartUpload(['Bucket' => self::STORAGE_BUCKET, 'Key' => 'key-2', 'UploadId' => 'upload-2'])->shouldBeCalledOnce();
        $this->s3Client->abortMultipartUpload(Argument::that(static fn ($input) => is_array($input) && (!isset($input['Key']) || in_array($input['Key'], ['no-key', 'no-upload-id'], true))))->shouldNotBeCalled();
        $this->s3Client->listObjectsV2(Argument::cetera())->willReturn($this->listOutput([]));

        $tester = $this->buildCommandTester();

        $this->assertSame(Command::SUCCESS, $tester->execute(['--force' => true]));
    }

    public function testDeletesObjectsInBatchesOfAtMost1000Keys(): void
    {
        $this->noMultipartUploads(self::STORAGE_BUCKET);
        $this->noMultipartUploads(self::UPLOAD_BUCKET);
        $keys = array_map(static fn (int $i) => sprintf('key-%d', $i), range(1, 2500));
        $this->s3Client->listObjectsV2(['Bucket' => self::STORAGE_BUCKET])->willReturn($this->listOutput($keys), $this->listOutput([]));
        $this->s3Client->listObjectsV2(['Bucket' => self::UPLOAD_BUCKET])->willReturn($this->listOutput([]));

        $batchSizes = [];
        // the batch sizes are checked below, the result object only has to report no errors
        $deleteOutput = $this->deleteOutput();
        $this->s3Client->deleteObjects(Argument::any())->will(function (array $args) use (&$batchSizes, $deleteOutput) {
            $batchSizes[] = count($args[0]['Delete']['Objects']);

            return $deleteOutput;
        });

        $tester = $this->buildCommandTester();
        $this->assertSame(Command::SUCCESS, $tester->execute(['--force' => true]));

        $this->assertSame([1000, 1000, 500], $batchSizes);
    }

    public function testFailedObjectDeletionAbortsWithErrorMessage(): void
    {
        $this->noMultipartUploads(self::STORAGE_BUCKET);
        $this->s3Client->listObjectsV2(['Bucket' => self::STORAGE_BUCKET])->willReturn($this->listOutput(['locked', 'other']));
        $this->s3Client->deleteObjects(Argument::any())->willReturn($this->deleteOutput([
            new Error(['Key' => 'locked', 'Message' => 'Access Denied']),
            new Error(['Key' => 'other', 'Code' => 'InternalError']),
            new Error([]),
        ]));

        $tester = $this->buildCommandTester(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unable to delete objects from bucket 'storage-bucket': locked (Access Denied), other (InternalError), ? (unknown error)");
        $tester->execute(['--force' => true]);
    }

    public function testBucketWhichNeverBecomesEmptyAbortsAfterIterationGuard(): void
    {
        $this->noMultipartUploads(self::STORAGE_BUCKET);
        $this->s3Client->listObjectsV2(['Bucket' => self::STORAGE_BUCKET])->willReturn($this->listOutput(['stuck']));
        $this->s3Client->deleteObjects(Argument::any())->shouldBeCalledTimes(10000)->willReturn($this->deleteOutput());

        $tester = $this->buildCommandTester(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Bucket 'storage-bucket' is not empty after 10000 delete rounds, aborting.");
        $tester->execute(['--force' => true]);
    }

    public function testMultipartUploadsWhichCanNotBeAbortedAbortAfterIterationGuard(): void
    {
        $this->s3Client->listMultipartUploads(['Bucket' => self::STORAGE_BUCKET])->willReturn($this->multipartOutput([['key', 'upload']]));
        $this->s3Client->abortMultipartUpload(Argument::any())->shouldBeCalledTimes(10000);

        $tester = $this->buildCommandTester(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Unable to abort all unfinished multipart uploads of bucket 'storage-bucket'.");
        $tester->execute(['--force' => true]);
    }

    public function testAbortsWhenConfirmationIsDeclined(): void
    {
        $this->s3Client->listObjectsV2(Argument::cetera())->shouldNotBeCalled();
        $this->redisClient->flushdb()->shouldNotBeCalled();

        $neo4jClient = $this->prophesize(ClientInterface::class);
        $neo4jClient->runStatement(Argument::any())->shouldNotBeCalled();
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($neo4jClient->reveal());
        $command = new DatabaseDropCommand(
            $cypherEntityManager->reveal(),
            $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->redisClient->reveal(),
            $this->prophesize(AMQPStreamConnection::class)->reveal(),
            $this->s3Client->reveal(),
            $this->prophesize(EmberNexusConfiguration::class)->reveal(),
        );
        $application = new Application();
        $application->addCommand($command);
        $tester = new CommandTester($command);
        $tester->setInputs(['no']);

        $exitCode = $tester->execute([]);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Aborted dropping databases.', $tester->getDisplay());
    }
}
