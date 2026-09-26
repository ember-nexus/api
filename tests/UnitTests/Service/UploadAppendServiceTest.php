<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\UploadInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Service\FileService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadAppendService;
use App\Service\UploadChunkValidator;
use App\Service\UploadFinalizationService;
use App\Service\UploadService;
use HashContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use RuntimeException;

#[Small]
#[CoversClass(UploadAppendService::class)]
class UploadAppendServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';

    private ObjectProphecy $uploadFactory;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $uploadFinalizationService;
    private ObjectProphecy $uploadChunkValidator;
    private ObjectProphecy $s3Service;
    private ObjectProphecy $fileOperationFactory;
    private ObjectProphecy $incrementalHashService;
    private ObjectProphecy $upload;
    private ObjectProphecy $nextUpload;
    private ObjectProphecy $completedUpload;
    private ObjectProphecy $partialUploadRequest;
    private FileOperationInterface $chunkFileOperation;
    /** @var resource */
    private $resource;

    /**
     * @param string[] $chunkIds
     */
    private function createService(
        ?int $declaredLength,
        bool $isComplete,
        int $bodyLength,
        int $requestOffset = 200,
        array $chunkIds = ['0123456789abcdef'],
        ?string $hashState = 'stored-state',
        bool $offsetMatches = true,
    ): UploadAppendService {
        $this->upload = $this->prophesize(UploadInterface::class);
        $this->upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $this->upload->getUploadOffset()->willReturn(200);
        $this->upload->getUploadLength()->willReturn(1000);
        $this->upload->getHashState()->willReturn($hashState);
        $this->upload->getChunkIds()->willReturn($chunkIds);
        $this->upload->getAlreadyUploadedChunks()->willReturn(count($chunkIds));

        $this->nextUpload = $this->prophesize(UploadInterface::class);
        $this->completedUpload = $this->prophesize(UploadInterface::class);

        $this->resource = fopen('php://memory', 'r+');
        fwrite($this->resource, str_repeat('a', $bodyLength));
        rewind($this->resource);

        $this->partialUploadRequest = $this->prophesize(PartialUploadRequestInterface::class);
        $this->partialUploadRequest->getUploadOffset()->willReturn($requestOffset);
        $this->partialUploadRequest->getContentLength()->willReturn($declaredLength);
        $this->partialUploadRequest->isUploadComplete()->willReturn($isComplete);
        $this->partialUploadRequest->getContent()->willReturn($this->resource);

        $this->uploadFactory = $this->prophesize(UploadFactory::class);
        $this->uploadFactory->addNewChunkToUpload(Argument::cetera())->willReturn($this->nextUpload->reveal());
        $this->uploadFactory->markUploadAsComplete(Argument::any())->willReturn($this->completedUpload->reveal());

        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->willReturn($offsetMatches);
        $this->uploadFinalizationService = $this->prophesize(UploadFinalizationService::class);
        $this->uploadChunkValidator = $this->prophesize(UploadChunkValidator::class);

        $this->s3Service = $this->prophesize(S3Service::class);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn($bodyLength);
        $this->s3Service->deleteFile(Argument::any())->will(function () {});
        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory
            ->createUploadFileChunkOperationFromPartialUploadRequest(Argument::cetera())
            ->willReturn($this->prophesize(UploadFileChunkOperationInterface::class)->reveal());
        $this->chunkFileOperation = $this->prophesize(FileOperationInterface::class)->reveal();
        $this->fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $this->fileOperationFactory->createFileOperationFromUpload(Argument::cetera())->willReturn($this->chunkFileOperation);

        $this->incrementalHashService = $this->prophesize(IncrementalHashService::class);
        $this->incrementalHashService->createContext(Argument::any())->willReturn(hash_init('sha256'));
        $this->incrementalHashService->unserializeContextFromStorage(Argument::any())->willReturn(hash_init('sha256'));
        $this->incrementalHashService->updateFromResource(Argument::cetera())->will(function () {});
        $this->incrementalHashService->serializeContextForStorage(Argument::any())->willReturn('next-state');

        $fileService = $this->prophesize(FileService::class);
        $fileService->generateUploadChunkId()->willReturn('aaaaaaaaaaaaaaaa');

        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::cetera())->will(
            fn ($args) => new Client409ConflictException('type', detail: $args[0])
        );

        return new UploadAppendService(
            $this->uploadFactory->reveal(),
            $this->uploadService->reveal(),
            $this->uploadFinalizationService->reveal(),
            $this->uploadChunkValidator->reveal(),
            $uploadFileChunkOperationFactory->reveal(),
            $this->fileOperationFactory->reveal(),
            $this->s3Service->reveal(),
            $this->incrementalHashService->reveal(),
            $fileService->reveal(),
            $client409ConflictExceptionFactory->reveal(),
        );
    }

    public function testOffsetOfRequestHasToMatchTheUpload(): void
    {
        $service = $this->createService(500, false, 500, requestOffset: 100);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        try {
            $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
            $this->fail('Expected exception was not thrown.');
        } catch (Client409ConflictException $exception) {
            $this->assertSame('Offset from request does not match offset of resource.', $exception->getDetail());
        }
    }

    public function testDeclaredEmptyIntermediateChunkIsANoOp(): void
    {
        $service = $this->createService(0, false, 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame($this->upload->reveal(), $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null));
        $this->assertFalse(is_resource($this->resource));
    }

    public function testUndeclaredEmptyIntermediateChunkIsANoOp(): void
    {
        $service = $this->createService(null, false, 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->assertSame($this->upload->reveal(), $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null));
    }

    public function testDeclaredInvalidChunkIsRejectedBeforeAnythingIsStored(): void
    {
        $service = $this->createService(50, false, 50);
        $this->uploadChunkValidator->assertValidChunk(50, false, 200, 1000)->willThrow(new Client400BadContentException('type', detail: 'too short'))->shouldBeCalledOnce();
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testIntermediateChunkContinuesTheStoredHashAndIsAppended(): void
    {
        $service = $this->createService(500, false, 500);
        $this->incrementalHashService->unserializeContextFromStorage('stored-state')->willReturn(hash_init('sha256'))->shouldBeCalledOnce();
        $this->incrementalHashService->createContext(Argument::any())->shouldNotBeCalled();
        $this->uploadChunkValidator->assertValidChunk(500, false, 200, 1000)->shouldBeCalledTimes(2);
        $this->uploadFactory->addNewChunkToUpload($this->upload->reveal(), 500, 'aaaaaaaaaaaaaaaa', 'next-state')->willReturn($this->nextUpload->reveal())->shouldBeCalledOnce();
        $this->uploadService->appendChunkIfOffsetMatches($this->upload->reveal(), $this->nextUpload->reveal())->willReturn(true)->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame($this->nextUpload->reveal(), $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null));
        $this->assertFalse(is_resource($this->resource));
    }

    public function testFirstIntermediateChunkStartsANewHash(): void
    {
        $service = $this->createService(500, false, 500, hashState: null);
        $this->incrementalHashService->createContext('sha256')->willReturn(hash_init('sha256'))->shouldBeCalledOnce();
        $this->incrementalHashService->unserializeContextFromStorage(Argument::any())->shouldNotBeCalled();

        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testChunkWithoutDeclaredLengthIsValidatedWithItsRealLength(): void
    {
        $service = $this->createService(null, false, 500);
        $this->uploadChunkValidator->assertValidChunk(500, false, 200, 1000)->shouldBeCalledOnce();

        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testCompletingChunkWithDataIsFinalizedWithTheHashContext(): void
    {
        $service = $this->createService(500, true, 500);
        $this->uploadFactory->addNewChunkToUpload($this->upload->reveal(), 500, 'aaaaaaaaaaaaaaaa', null)->willReturn($this->nextUpload->reveal())->shouldBeCalledOnce();
        $this->uploadFactory->markUploadAsComplete($this->nextUpload->reveal())->willReturn($this->completedUpload->reveal())->shouldBeCalledOnce();
        $this->uploadService->appendChunkIfOffsetMatches($this->upload->reveal(), $this->completedUpload->reveal())->willReturn(true)->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize($this->completedUpload->reveal(), Argument::type(HashContext::class), 'sha-256=:abc=:')->shouldBeCalledOnce();

        $this->assertSame($this->completedUpload->reveal(), $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), 'sha-256=:abc=:'));
    }

    public function testCompletingRequestWithoutBodyCompletesAnUploadWithChunksWithoutStoringAnything(): void
    {
        $service = $this->createService(0, true, 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadFactory->addNewChunkToUpload(Argument::cetera())->shouldNotBeCalled();
        $this->uploadFactory->markUploadAsComplete($this->upload->reveal())->willReturn($this->completedUpload->reveal())->shouldBeCalledOnce();
        $this->uploadChunkValidator->assertValidChunk(0, true, 200, 1000)->shouldBeCalledTimes(2);
        $this->uploadService->appendChunkIfOffsetMatches($this->upload->reveal(), $this->completedUpload->reveal())->willReturn(true)->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize($this->completedUpload->reveal(), Argument::type(HashContext::class), null)->shouldBeCalledOnce();

        $this->assertSame($this->completedUpload->reveal(), $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null));
        $this->assertFalse(is_resource($this->resource));
    }

    public function testCompletingRequestWithoutBodyStoresTheEmptyChunkIfTheUploadHasNoChunk(): void
    {
        $service = $this->createService(0, true, 0, chunkIds: [], hashState: null);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn(0)->shouldBeCalledOnce();
        $this->uploadFactory->addNewChunkToUpload($this->upload->reveal(), 0, 'aaaaaaaaaaaaaaaa', null)->willReturn($this->nextUpload->reveal())->shouldBeCalledOnce();
        $this->uploadFactory->markUploadAsComplete($this->nextUpload->reveal())->willReturn($this->completedUpload->reveal());

        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testChunkWhichIsRejectedAfterItWasStoredIsDeleted(): void
    {
        $service = $this->createService(null, false, 500);
        // without declared length the chunk can only be validated once its real length is known
        $this->uploadChunkValidator->assertValidChunk(500, false, 200, 1000)->willThrow(new Client400BadContentException('type', detail: 'real length is invalid'))->shouldBeCalledOnce();
        $this->fileOperationFactory->createFileOperationFromUpload($this->upload->reveal(), 2, 'aaaaaaaaaaaaaaaa')->willReturn($this->chunkFileOperation)->shouldBeCalledOnce();
        $this->s3Service->deleteFile($this->chunkFileOperation)->shouldBeCalledOnce();
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testConcurrentModificationIsAnsweredWithConflictAndDeletesOwnChunk(): void
    {
        $service = $this->createService(500, false, 500, offsetMatches: false);
        $this->s3Service->deleteFile($this->chunkFileOperation)->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();

        try {
            $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
            $this->fail('Expected exception was not thrown.');
        } catch (Client409ConflictException $exception) {
            $this->assertStringContainsString('modified by another request', $exception->getDetail());
        }
    }

    public function testConcurrentModificationOfACompletingRequestWithoutBodyDeletesNothing(): void
    {
        $service = $this->createService(0, true, 0, offsetMatches: false);
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }

    public function testFailedFinalizationIsNotHandledHere(): void
    {
        $service = $this->createService(500, true, 500);
        $this->uploadFinalizationService->finalize(Argument::cetera())->willThrow(new RuntimeException('merge failed'));
        // the chunk is part of the upload now, it must survive so that the completion can be retried
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $service->append($this->upload->reveal(), $this->partialUploadRequest->reveal(), null);
    }
}
