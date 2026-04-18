<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\S3\FileOperationInterface;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Service\S3Service;
use App\Wrapper\S3ClientWrapper;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\ObjectExistsWaiter;
use AsyncAws\S3\S3Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(S3Service::class)]
class S3ServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildS3Service(
        ?S3Client $s3Client = null,
        ?UploadFileChunkOperationFactory $fileChunkOperationFactory = null,
        ?S3ClientWrapper $s3ClientWrapper = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory = null,
    ): S3Service {
        return new S3Service(
            $s3Client ?? $this->prophesize(S3Client::class)->reveal(),
            $fileChunkOperationFactory ?? $this->prophesize(UploadFileChunkOperationFactory::class)->reveal(),
            $s3ClientWrapper ?? $this->prophesize(S3ClientWrapper::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testExistsFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($objectExistsWaiter);

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledOnce()->willReturn(true);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $output = $s3Service->existsFile($fileOperation);
        $this->assertSame(true, $output);
    }

    public function testGetFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $getObjectOutput = $this->prophesize(GetObjectOutput::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $output = $s3Service->getFile($fileOperation);
        $this->assertSame($getObjectOutput, $output);
    }

    public function testGetFileAsResource(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileAsResource($fileOperation);
        $this->assertSame('some content', $resource);
    }

    public function testGetEtag(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getETag()->shouldBeCalledOnce()->willReturn('some etag');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $etag = $s3Service->getEtag($fileOperation);
        $this->assertSame('some etag', $etag);
    }

    public function testGetEtagThrowsWhenEtagIsNull(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getETag()->shouldBeCalledOnce()->willReturn(null);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to retrieve file.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->getEtag($fileOperation);
    }
}
