<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\NodeElement;
use App\Type\S3\FileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(FileOperationFactory::class)]
class FileOperationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildFileOperationFactory(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?FileService $fileService = null,
        ?ElementService $elementService = null,
        ?Server500LogicErrorExceptionFactory $server500LogicExceptionFactory = null,
    ): FileOperationFactory {
        return new FileOperationFactory(
            $emberNexusConfiguration ?? $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $fileService ?? $this->prophesize(FileService::class)->reveal(),
            $elementService ?? $this->prophesize(ElementService::class)->reveal(),
            $server500LogicExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
    }

    public function testCreateFileOperationFromUpload(): void
    {
        $id = Uuid::fromString('2e92baf4-850e-4a98-ab03-a5b2f717808f');

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($id), Argument::is(3))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildFileOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            fileService: $fileService->reveal()
        );

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($id);

        $operation = $factory->createFileOperationFromUpload($upload->reveal(), 3);

        $this->assertInstanceOf(FileOperation::class, $operation);
        $this->assertSame('upload-bucket', $operation->getBucket());
        $this->assertSame('upload-key', $operation->getKey());
    }

    public function testCreateFileOperationFromElement(): void
    {
        $id = Uuid::fromString('2e92baf4-850e-4a98-ab03-a5b2f717808f');

        $element = $this->prophesize(NodeElement::class);
        $element->getId()->shouldBeCalledOnce()->willReturn($id);
        $element = $element->reveal();

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('test');

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $fileService = $this->prophesize(FileService::class);
        $fileService->getStorageBucketKey(Argument::is($id), Argument::is('test'))->shouldBeCalledOnce()->willReturn('storage-key.test');

        $factory = $this->buildFileOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            fileService: $fileService->reveal(),
            elementService: $elementService->reveal()
        );

        $operation = $factory->createFileOperationFromElement($element);

        $this->assertInstanceOf(FileOperation::class, $operation);
        $this->assertSame('storage-bucket', $operation->getBucket());
        $this->assertSame('storage-key.test', $operation->getKey());
    }

    public function testCreateFileOperationFromElementFailsOnMissingElementId(): void
    {
        $element = $this->prophesize(NodeElementInterface::class)->reveal();
        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicExceptionFactory->createFromTemplate(Argument::is('Expected elementId to be not null.'))->shouldBeCalledOnce()->willReturn($exception);

        $factory = $this->buildFileOperationFactory(
            server500LogicExceptionFactory: $server500LogicExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $factory->createFileOperationFromElement($element);
    }
}
