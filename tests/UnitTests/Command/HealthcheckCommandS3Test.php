<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\HealthcheckCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use AsyncAws\Core\Response;
use AsyncAws\Core\Test\Http\SimpleMockedResponse;
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
use ReflectionClass;
use ReflectionMethod;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * The S3 part of the healthcheck.
 */
#[Small]
#[CoversClass(HealthcheckCommand::class)]
class HealthcheckCommandS3Test extends TestCase
{
    use ProphecyTrait;

    /**
     * @param array<string, string|list<string>> $headers
     */
    private function bucketWaiter(bool $isSuccess, array $headers = []): BucketExistsWaiter
    {
        $responseReflection = new ReflectionClass(Response::class);
        $response = $responseReflection->newInstanceWithoutConstructor();
        $responseReflection->getProperty('resolveResult')->setValue($response, true);
        $responseReflection->getProperty('bodyDownloaded')->setValue($response, true);
        $responseReflection->getProperty('httpResponse')->setValue($response, new SimpleMockedResponse('', $headers));

        /** @var BucketExistsWaiter $waiter */
        $waiter = (new ReflectionClass(BucketExistsWaiter::class))->newInstanceWithoutConstructor();
        $waiterReflection = new ReflectionClass(Waiter::class);
        $waiterReflection->getProperty('response')->setValue($waiter, $response);
        $waiterReflection->getProperty('finalState')->setValue($waiter, $isSuccess ? Waiter::STATE_SUCCESS : Waiter::STATE_FAILURE);

        return $waiter;
    }

    /**
     * @param array<string, string|list<string>> $storageBucketHeaders
     */
    private function getS3Provider(bool $isStorageBucketOnline, bool $isUploadBucketOnline, array $storageBucketHeaders = []): string
    {
        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->bucketExists(['Bucket' => 'storage-bucket'])->willReturn($this->bucketWaiter($isStorageBucketOnline, $storageBucketHeaders));
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

        return (new ReflectionMethod($command, 'getS3Provider'))->invoke($command);
    }

    public function testStorageBucketOfflineFailsBeforeCheckingUploadBucket(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to connect to bucket storage-bucket.');

        $this->getS3Provider(false, true);
    }

    public function testUploadBucketOfflineFails(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to connect to bucket upload-bucket.');

        $this->getS3Provider(true, false);
    }

    public function testServerHeaderIsUsedAsProviderName(): void
    {
        $this->assertSame('MinIO', $this->getS3Provider(true, true, ['Server' => 'MinIO']));
    }

    public function testServerHeaderNameIsMatchedCaseInsensitively(): void
    {
        $this->assertSame('MinIO', $this->getS3Provider(true, true, ['server' => 'MinIO']));
    }

    public function testMissingServerHeaderFallsBackToUnknown(): void
    {
        $this->assertSame('Unknown', $this->getS3Provider(true, true, []));
    }
}
