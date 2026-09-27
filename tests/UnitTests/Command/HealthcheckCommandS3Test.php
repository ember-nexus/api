<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\HealthcheckCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use AsyncAws\Core\Test\ResultMockFactory;
use AsyncAws\Core\Waiter;
use AsyncAws\S3\Result\BucketExistsWaiter;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionMethod;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * The S3 part of the healthcheck; see HealthcheckCommandVersionsTest for the other database checks and why the
 * remaining ones stay covered only by the command example test.
 */
#[Small]
#[CoversClass(HealthcheckCommand::class)]
class HealthcheckCommandS3Test extends TestCase
{
    use ProphecyTrait;

    private function bucketWaiter(bool $isSuccess): BucketExistsWaiter
    {
        /** @var BucketExistsWaiter */
        return ResultMockFactory::waiter(BucketExistsWaiter::class, $isSuccess ? Waiter::STATE_SUCCESS : Waiter::STATE_FAILURE);
    }

    private function getS3Status(bool $isStorageBucketOnline, bool $isUploadBucketOnline): string
    {
        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->bucketExists(['Bucket' => 'storage-bucket'])->willReturn($this->bucketWaiter($isStorageBucketOnline));
        if ($isStorageBucketOnline) {
            $s3Client->bucketExists(['Bucket' => 'upload-bucket'])->willReturn($this->bucketWaiter($isUploadBucketOnline));
        } else {
            $s3Client->bucketExists(['Bucket' => 'upload-bucket'])->shouldNotBeCalled();
        }

        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileS3StorageBucket()->willReturn('storage-bucket');
        $configuration->getFileS3UploadBucket()->willReturn('upload-bucket');

        $command = new HealthcheckCommand(
            $this->prophesize(CypherEntityManager::class)->reveal(),
            $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(RedisClient::class)->reveal(),
            $this->prophesize(AMQPStreamConnection::class)->reveal(),
            $s3Client->reveal(),
            $configuration->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );

        return (new ReflectionMethod($command, 'getS3Status'))->invoke($command);
    }

    public function testBothBucketsOnline(): void
    {
        $this->assertSame('online', $this->getS3Status(true, true));
    }

    public function testStorageBucketOfflineFailsBeforeCheckingUploadBucket(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to connect to bucket storage-bucket.');

        $this->getS3Status(false, true);
    }

    public function testUploadBucketOfflineFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to connect to bucket upload-bucket.');

        $this->getS3Status(true, false);
    }
}
