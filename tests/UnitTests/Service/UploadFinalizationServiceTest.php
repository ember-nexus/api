<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\UploadInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Exception\Client400BadContentException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Service\DigestService;
use App\Service\ElementFileDeletionService;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileCreationLockService;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadFinalizationService;
use App\Service\UploadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use RuntimeException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(UploadFinalizationService::class)]
class UploadFinalizationServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string HASH = '2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824';

    private UuidInterface $targetId;
    private ObjectProphecy $upload;
    private ObjectProphecy $element;
    private ObjectProphecy $elementManager;
    private ObjectProphecy $eventDispatcher;
    private ObjectProphecy $s3Service;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $fileSizeLimitService;
    private ObjectProphecy $elementFileDeletionService;
    private ObjectProphecy $incrementalHashService;
    private ObjectProphecy $logger;
    private ObjectProphecy $elementService;
    private ObjectProphecy $fileCreationLockService;
    private UploadFinalizationService $service;

    protected function setUp(): void
    {
        $this->targetId = Uuid::uuid4();
        $this->upload = $this->prophesize(UploadInterface::class);
        $this->upload->getUploadTarget()->willReturn($this->targetId);
        $this->upload->targetHadFileAtCreation()->willReturn(false);
        $this->upload->getId()->willReturn(Uuid::uuid4());
        $this->upload->getUploadOffset()->willReturn(5);
        $this->upload->getExtension()->willReturn('txt');

        $this->element = $this->prophesize(NodeElementInterface::class);
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->getElementOrFail(Argument::any())->willReturn($this->element->reveal());
        $this->elementManager->merge(Argument::any())->willReturn($this->elementManager->reveal());
        $this->elementManager->delete(Argument::any())->willReturn($this->elementManager->reveal());
        $this->elementManager->flush()->willReturn($this->elementManager->reveal());

        $mergeOperation = $this->prophesize(MergeFileChunksOperationInterface::class)->reveal();
        $mergeFactory = $this->prophesize(S3OperationFactory::class);
        $mergeFactory->createMergeFileOperationFromUpload(Argument::any())->willReturn($mergeOperation);

        $this->s3Service = $this->prophesize(S3Service::class);
        $this->s3Service->getChunksContentLength(Argument::any())->willReturn(5);
        $this->s3Service->mergeFileChunks(Argument::any())->willReturn(5);
        $this->s3Service->getMimeTypeFromMergeFileChunksOperation(Argument::any())->willReturn('text/plain');
        $this->s3Service->deleteFileChunks(Argument::any())->will(function () {});

        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadService->deleteUpload(Argument::any())->will(function () {});
        $this->uploadService->markUploadAsUnfinalized(Argument::cetera())->willReturn(true);
        $this->incrementalHashService = $this->prophesize(IncrementalHashService::class);
        $this->incrementalHashService->serializeContextForStorage(Argument::any())->willReturn('final-state');
        $this->incrementalHashService->finalize(Argument::any())->willReturn(self::HASH);
        $this->logger = $this->prophesize(LoggerInterface::class);
        $this->elementFileDeletionService = $this->prophesize(ElementFileDeletionService::class);
        $this->elementFileDeletionService->deletePreviousFileAfterReplace(Argument::any())->will(function () {});
        $this->fileSizeLimitService = $this->prophesize(FileSizeLimitService::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->eventDispatcher->dispatch(Argument::any())->will(fn (array $args) => $args[0]);

        $badContentFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $badContentFactory->createFromDetail(Argument::type('string'))->will(
            fn (array $args) => new Client400BadContentException('bad-content', detail: $args[0])
        );

        $conflictFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $conflictFactory->createFromDetail(Argument::type('string'))->will(
            fn (array $args) => new Client409ConflictException('conflict', detail: $args[0])
        );

        $this->elementService = $this->prophesize(ElementService::class);
        $this->elementService->hasFile(Argument::any())->willReturn(false);

        $this->fileCreationLockService = $this->prophesize(FileCreationLockService::class);
        $this->fileCreationLockService->acquire(Argument::any())->willReturn('lock-token');
        $this->fileCreationLockService->release(Argument::any(), Argument::any())->will(function () {});

        $this->service = new UploadFinalizationService(
            $this->elementManager->reveal(),
            $this->eventDispatcher->reveal(),
            $mergeFactory->reveal(),
            $this->s3Service->reveal(),
            $this->uploadService->reveal(),
            new DigestService(),
            $this->fileSizeLimitService->reveal(),
            $badContentFactory->reveal(),
            $conflictFactory->reveal(),
            $this->logger->reveal(),
            $this->elementFileDeletionService->reveal(),
            $this->incrementalHashService->reveal(),
            $this->elementService->reveal(),
            $this->fileCreationLockService->reveal(),
        );
    }

    private function digestHeader(string $hexHash = self::HASH): string
    {
        return sprintf('sha-256=:%s:', base64_encode(\Safe\hex2bin($hexHash)));
    }

    private function expectFileToBeMerged(): void
    {
        $this->s3Service->mergeFileChunks(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty('file', [
            'contentLength' => 5,
            'extension' => 'txt',
            'mimeType' => 'text/plain',
            'hash' => ['sha256' => self::HASH],
        ])->shouldBeCalledOnce()->willReturn($this->element->reveal());
        $this->element->addProperty('hasFile', true)->shouldBeCalledOnce()->willReturn($this->element->reveal());
        $this->uploadService->deleteUpload(Argument::any())->shouldBeCalledOnce();
        $this->eventDispatcher->dispatch(Argument::that(
            fn ($event) => $event instanceof ElementFileReplaceEvent
        ))->shouldBeCalledOnce();
    }

    private function expectUploadToBeDiscarded(): void
    {
        $this->s3Service->mergeFileChunks(Argument::any())->shouldNotBeCalled();
        $this->s3Service->deleteFileChunks(Argument::any())->shouldBeCalledOnce();
        $this->uploadService->deleteUpload(Argument::any())->shouldBeCalledOnce();
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();
    }

    public function testFinalizeWithoutDigestMergesChunksAndSetsFile(): void
    {
        $this->expectFileToBeMerged();

        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFinalizeDeletesPreviousFileOnlyAfterFlushOfTheElement(): void
    {
        $log = [];
        $this->s3Service->mergeFileChunks(Argument::any())->will(function () use (&$log) {
            $log[] = 'merge-chunks';

            return 5;
        });
        $this->element->addProperty(Argument::cetera())->willReturn($this->element->reveal());
        $this->elementManager->flush()->will(function () use (&$log) {
            $log[] = 'flush';

            return $this->reveal();
        });
        $this->elementFileDeletionService->deletePreviousFileAfterReplace(Argument::any())->will(function () use (&$log) {
            $log[] = 'delete-previous';
        });
        $this->s3Service->deleteFileChunks(Argument::any())->will(function () use (&$log) {
            $log[] = 'delete-chunks';
        });

        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));

        $this->assertSame(['merge-chunks', 'flush', 'delete-previous', 'delete-chunks', 'flush'], $log);
    }

    public function testFinalizeWithFailingFlushDoesNotDeletePreviousFileOrChunks(): void
    {
        $this->element->addProperty(Argument::cetera())->willReturn($this->element->reveal());
        $this->elementManager->flush()->willThrow(new RuntimeException('flush failed'));
        $this->elementFileDeletionService->deletePreviousFileAfterReplace(Argument::any())->shouldNotBeCalled();
        $this->s3Service->deleteFileChunks(Argument::any())->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFinalizeWithMatchingReprDigestMergesChunks(): void
    {
        $this->expectFileToBeMerged();

        $this->service->finalize($this->upload->reveal(), hash_init('sha256'), $this->digestHeader());
    }

    public function testFinalizeWithMismatchingDigestDiscardsUpload(): void
    {
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), hash_init('sha256'), $this->digestHeader(hash('sha256', 'other')));
            $this->fail('Expected digest mismatch to be rejected.');
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString('does not match', $exception->getDetail());
        }
    }

    public function testFinalizeWithUnsupportedDigestAlgorithmDiscardsUpload(): void
    {
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), hash_init('sha256'), 'md5=:AAAA:');
            $this->fail('Expected unsupported digest algorithm to be rejected.');
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString("'Repr-Digest' header", $exception->getDetail());
            $this->assertStringContainsString('only \'sha-256\' is supported', $exception->getDetail());
        }
    }

    public function testFinalizeAboveMaxFileSizeDiscardsUpload(): void
    {
        $this->fileSizeLimitService->assertWithinMaxFileSize(5)->shouldBeCalledOnce()->willThrow(new Client400BadContentException('too-big'));
        $this->expectUploadToBeDiscarded();

        $this->expectException(Client400BadContentException::class);
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFinalizeWithStoredChunksNotAddingUpToOffsetDiscardsUpload(): void
    {
        $this->s3Service->getChunksContentLength(Argument::any())->willReturn(4);
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), hash_init('sha256'), $this->digestHeader());
            $this->fail('Expected inconsistent upload to be rejected.');
        } catch (Client409ConflictException $exception) {
            $this->assertStringContainsString('restart the upload', $exception->getDetail());
        }
    }

    public function testFinalizeWithMissingChunkDiscardsUpload(): void
    {
        $this->s3Service->getChunksContentLength(Argument::any())->willReturn(null);
        $this->expectUploadToBeDiscarded();

        $this->expectException(Client409ConflictException::class);
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFailedMergeOfTheChunksPutsTheUploadBackToUnfinalized(): void
    {
        $this->s3Service->mergeFileChunks(Argument::any())->willThrow(new RuntimeException('merge failed'));
        $this->uploadService->markUploadAsUnfinalized($this->upload->reveal(), 'final-state')->willReturn(true)->shouldBeCalledOnce();
        // all chunks stay, so that the client can complete the upload again
        $this->s3Service->deleteFileChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadService->deleteUpload(Argument::any())->shouldNotBeCalled();
        $this->eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('merge failed');
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFailedFlushOfTheElementPutsTheUploadBackToUnfinalized(): void
    {
        $this->element->addProperty(Argument::cetera())->willReturn($this->element->reveal());
        $this->elementManager->flush()->willThrow(new RuntimeException('flush failed'));
        $this->uploadService->markUploadAsUnfinalized($this->upload->reveal(), 'final-state')->willReturn(true)->shouldBeCalledOnce();
        $this->elementFileDeletionService->deletePreviousFileAfterReplace(Argument::any())->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('flush failed');
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFailureWhilePuttingTheUploadBackIsLoggedAndTheOriginalFailureIsThrown(): void
    {
        $this->s3Service->mergeFileChunks(Argument::any())->willThrow(new RuntimeException('merge failed'));
        $this->uploadService->markUploadAsUnfinalized(Argument::cetera())->willThrow(new RuntimeException('graph down'));
        $this->logger->error(Argument::containingString('graph down'))->shouldBeCalledOnce();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('merge failed');
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testUploadWhichCanNotBePutBackIsLogged(): void
    {
        $this->s3Service->mergeFileChunks(Argument::any())->willThrow(new RuntimeException('merge failed'));
        $this->uploadService->markUploadAsUnfinalized(Argument::cetera())->willReturn(false);
        $this->logger->error(Argument::containingString('does not exist or is not complete anymore'))->shouldBeCalledOnce();

        $this->expectException(RuntimeException::class);
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFinalizeWithHasFileAlreadyTrueDiscardsUpload(): void
    {
        $this->elementService->hasFile(Argument::any())->willReturn(true);
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
            $this->fail('Expected an already existing file to be rejected.');
        } catch (Client409ConflictException $exception) {
            $this->assertStringContainsString('already has an associated file', $exception->getDetail());
        }
    }

    public function testFinalizeSucceedsWithHasFileTrueWhenTargetAlreadyHadAFileAtUploadCreation(): void
    {
        $this->upload->targetHadFileAtCreation()->willReturn(true);
        $this->elementService->hasFile(Argument::any())->willReturn(true);
        $this->expectFileToBeMerged();

        $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
    }

    public function testFinalizeWithFailedLockAcquisitionDiscardsUpload(): void
    {
        $this->fileCreationLockService->acquire(Argument::any())->willReturn(null);
        $this->fileCreationLockService->release(Argument::cetera())->shouldNotBeCalled();
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), hash_init('sha256'));
            $this->fail('Expected a failed lock acquisition to be rejected.');
        } catch (Client409ConflictException $exception) {
            $this->assertStringContainsString('currently creating the file', $exception->getDetail());
        }
    }

    public function testVerdictsAboutTheWholeUploadDoNotPutItBack(): void
    {
        $this->uploadService->markUploadAsUnfinalized(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
        $this->service->finalize($this->upload->reveal(), hash_init('sha256'), $this->digestHeader(hash('sha256', 'other')));
    }
}
