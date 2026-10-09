<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\UploadInterface;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Service\ElementManager;
use App\Service\S3Service;
use App\Service\UploadConsistencyService;
use App\Service\UploadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(UploadConsistencyService::class)]
class UploadConsistencyServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $elementManager;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $s3Service;
    private ObjectProphecy $logger;
    private UploadConsistencyService $service;

    protected function setUp(): void
    {
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->logger = $this->prophesize(LoggerInterface::class);

        $fileOperationFactory = $this->prophesize(S3OperationFactory::class);
        $fileOperationFactory->createFileOperationFromUpload(Argument::cetera())->willReturn($this->prophesize(FileOperationInterface::class)->reveal());
        $conflictFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $conflictFactory->createFromDetail(Argument::type('string'))->will(
            fn (array $args) => new Client409ConflictException('conflict', detail: $args[0])
        );

        $this->service = new UploadConsistencyService(
            $this->elementManager->reveal(),
            $this->uploadService->reveal(),
            $this->s3Service->reveal(),
            $fileOperationFactory->reveal(),
            $conflictFactory->reveal(),
            $this->logger->reveal(),
        );
    }

    /**
     * @param list<string> $chunkIds
     */
    private function createUpload(array $chunkIds, int $offset): UploadInterface
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn(Uuid::uuid4());
        $upload->getChunkIds()->willReturn($chunkIds);
        $upload->getLastChunkId()->willReturn([] === $chunkIds ? null : $chunkIds[array_key_last($chunkIds)]);
        $upload->getUploadOffset()->willReturn($offset);

        return $upload->reveal();
    }

    private function createElement(mixed $lastChunkId, bool $hasProperty = true): NodeElementInterface
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty('lastChunkId')->willReturn($hasProperty);
        $element->getProperty('lastChunkId')->willReturn($lastChunkId);

        return $element->reveal();
    }

    private function expectNothingToBeDeleted(): void
    {
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();
        $this->elementManager->flush()->shouldNotBeCalled();
    }

    public function testUploadWithoutChunksIsConsistent(): void
    {
        $this->expectNothingToBeDeleted();

        $this->service->assertConsistent($this->createElement(null, false), $this->createUpload([], 0));
        $this->service->assertConsistent($this->createElement(null), $this->createUpload([], 0));
    }

    public function testUploadWithMatchingLastChunkIdIsConsistent(): void
    {
        $this->expectNothingToBeDeleted();

        $this->service->assertConsistent($this->createElement('bbbb'), $this->createUpload(['aaaa', 'bbbb'], 10));
    }

    public function testUploadWithLastChunkIdMissingInListIsDeletedIncludingTheUnlistedChunk(): void
    {
        $upload = $this->createUpload(['aaaa'], 10);
        $this->s3Service->deleteFile(Argument::any())->shouldBeCalledOnce();
        $this->uploadService->deleteUploadAndChunks($upload)->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->logger->error(Argument::type('string'))->shouldBeCalledOnce();

        try {
            $this->service->assertConsistent($this->createElement('bbbb'), $upload);
            $this->fail('Expected inconsistent upload to be rejected.');
        } catch (Client409ConflictException $exception) {
            $this->assertStringContainsString('restart the upload', $exception->getDetail());
        }
    }

    public function testUploadWithListButWithoutLastChunkIdInGraphIsDeleted(): void
    {
        $upload = $this->createUpload(['aaaa'], 10);
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();
        $this->uploadService->deleteUploadAndChunks($upload)->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->logger->error(Argument::type('string'))->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $this->service->assertConsistent($this->createElement(null, false), $upload);
    }

    public function testUploadWithOffsetButWithoutChunksIsDeleted(): void
    {
        $upload = $this->createUpload([], 10);
        $this->uploadService->deleteUploadAndChunks($upload)->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->logger->error(Argument::type('string'))->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $this->service->assertConsistent($this->createElement(null), $upload);
    }

    /**
     * The symmetric case of {@see testUploadWithOffsetButWithoutChunksIsDeleted()}: a non-empty chunk list can not
     * coincide with a zero offset either.
     */
    public function testUploadWithChunksButZeroOffsetIsDeleted(): void
    {
        $upload = $this->createUpload(['aaaa'], 0);
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();
        $this->uploadService->deleteUploadAndChunks($upload)->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->logger->error(Argument::type('string'))->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $this->service->assertConsistent($this->createElement('aaaa'), $upload);
    }

    public function testUploadWithInvalidLastChunkIdIsDeleted(): void
    {
        $upload = $this->createUpload(['aaaa'], 10);
        $this->uploadService->deleteUploadAndChunks($upload)->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->logger->error(Argument::type('string'))->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $this->service->assertConsistent($this->createElement(['not', 'a', 'string']), $upload);
    }
}
