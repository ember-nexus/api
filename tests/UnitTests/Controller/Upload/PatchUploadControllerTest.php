<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\NodeElementInterface;
use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\UploadInterface;
use App\Controller\Upload\PatchUploadController;
use App\Exception\Client400BadContentException;
use App\Exception\Client404NotFoundException;
use App\Exception\Client409ConflictException;
use App\Exception\ProblemJsonException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\FileService;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadCancellationService;
use App\Service\UploadFinalizationService;
use App\Service\UploadLockService;
use App\Service\UploadService;
use App\Type\AccessType;
use App\Type\Response\NoContentResponse;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\Request;

/**
 * Chunks which can be rejected from the declared `Content-Length` must be rejected before anything is sent to S3.
 */
#[Small]
#[CoversClass(PatchUploadController::class)]
class PatchUploadControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';
    private const string USER_ID = '11111111-1111-4111-8111-111111111111';
    private const string TARGET_ID = '22222222-2222-4222-8222-222222222222';
    private const int MIN_CHUNK_SIZE = 100;
    private const int MAX_CHUNK_SIZE = 1000;

    private ObjectProphecy $s3Service;
    private ObjectProphecy $fileSizeLimitService;
    private ObjectProphecy $uploadLockService;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $uploadFinalizationService;
    private ObjectProphecy $fileOperationFactory;
    private ObjectProphecy $uploadCancellationService;
    private ObjectProphecy $accessChecker;
    private ObjectProphecy $client404NotFoundExceptionFactory;

    /**
     * @param int|null $declaredContentLength `Content-Length` of the request, null if the header is missing
     */
    private function createController(
        ?int $declaredContentLength,
        bool $isUploadComplete = false,
        int $uploadOffset = 0,
        ?int $uploadLength = null,
        int $s3ChunkLength = 500,
        bool $hasAccess = true,
        bool $offsetMatches = true,
    ): PatchUploadController {
        $userId = Uuid::fromString(self::USER_ID);
        $targetId = Uuid::fromString(self::TARGET_ID);

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $upload->getUploadOwner()->willReturn($userId);
        $upload->getUploadTarget()->willReturn($targetId);
        $upload->getExpires()->willReturn(new DateTime('+1 day'));
        $upload->getUploadOffset()->willReturn($uploadOffset);
        $upload->getUploadLength()->willReturn($uploadLength);
        $upload->getHashState()->willReturn(null);
        $upload->getChunkIds()->willReturn(['0123456789abcdef']);
        $upload->getAlreadyUploadedChunks()->willReturn(1);

        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement(Argument::any())->willReturn($upload->reveal());
        $uploadFactory->addNewChunkToUpload(Argument::cetera())->willReturn($upload->reveal());
        $uploadFactory->markUploadAsComplete(Argument::any())->willReturn($upload->reveal());

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::any())->willReturn($this->prophesize(NodeElementInterface::class)->reveal());
        $elementManager->flush()->willReturn($elementManager->reveal());

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);

        $this->accessChecker = $this->prophesize(AccessChecker::class);
        $this->accessChecker->hasAccessToElement($userId, $targetId, AccessType::UPDATE)->willReturn($hasAccess);

        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileUploadMinChunkSizeInBytes()->willReturn(self::MIN_CHUNK_SIZE);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn(self::MAX_CHUNK_SIZE);

        $partialUploadRequest = $this->prophesize(PartialUploadRequestInterface::class);
        $partialUploadRequest->getUploadOffset()->willReturn($uploadOffset);
        $partialUploadRequest->getContentLength()->willReturn($declaredContentLength);
        $partialUploadRequest->isUploadComplete()->willReturn($isUploadComplete);
        // the request body is buffered, so its real size is available even without `Content-Length`
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, str_repeat('a', $s3ChunkLength));
        rewind($resource);
        $partialUploadRequest->getContent()->willReturn($resource);
        $partialUploadRequestFactory = $this->prophesize(PartialUploadRequestFactory::class);
        $partialUploadRequestFactory->createPartialUploadRequestFromRequest(Argument::any())->willReturn($partialUploadRequest->reveal());

        // reaching S3 means the chunk was not rejected beforehand; the real length is only known afterwards
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn($s3ChunkLength);
        $this->s3Service->deleteFile(Argument::any())->will(function () {});

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory
            ->createUploadFileChunkOperationFromPartialUploadRequest(Argument::cetera())
            ->willReturn($this->prophesize(UploadFileChunkOperationInterface::class)->reveal());

        $incrementalHashService = $this->prophesize(IncrementalHashService::class);
        $incrementalHashService->createContext(Argument::any())->willReturn(hash_init('sha256'));
        $incrementalHashService->updateFromResource(Argument::cetera())->will(function () {});
        $incrementalHashService->serializeContextForStorage(Argument::any())->willReturn('state');
        $incrementalHashService->finalize(Argument::any())->willReturn('hash');

        $this->fileSizeLimitService = $this->prophesize(FileSizeLimitService::class);
        $this->uploadLockService = $this->prophesize(UploadLockService::class);
        $this->uploadLockService->acquire(Argument::any())->willReturn('token');
        $this->uploadLockService->release(Argument::cetera())->will(function () {});

        $noContentResponseFactory = $this->prophesize(NoContentResponseFactory::class);
        $noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload(Argument::cetera())->willReturn(new NoContentResponse());
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->willReturn($offsetMatches);
        $this->uploadFinalizationService = $this->prophesize(UploadFinalizationService::class);
        $this->fileOperationFactory = $this->prophesize(FileOperationFactory::class);
        $this->fileOperationFactory->createFileOperationFromUpload(Argument::cetera())->willReturn($this->prophesize(FileOperationInterface::class)->reveal());
        $fileService = $this->prophesize(FileService::class);
        $fileService->generateUploadChunkId()->willReturn('aaaaaaaaaaaaaaaa');
        $this->uploadCancellationService = $this->prophesize(UploadCancellationService::class);
        $this->client404NotFoundExceptionFactory = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $this->client404NotFoundExceptionFactory->createFromTemplate(Argument::cetera())->will(
            fn () => new Client404NotFoundException('type')
        );

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::any())->will(
            fn ($args) => new Client400BadContentException('type', detail: $args[0])
        );
        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::cetera())->will(
            fn ($args) => new Client409ConflictException('type', detail: $args[0])
        );

        return new PatchUploadController(
            $authProvider->reveal(),
            $this->accessChecker->reveal(),
            $elementManager->reveal(),
            $configuration->reveal(),
            $partialUploadRequestFactory->reveal(),
            $noContentResponseFactory->reveal(),
            $uploadFactory->reveal(),
            $this->uploadService->reveal(),
            $this->uploadFinalizationService->reveal(),
            $this->uploadCancellationService->reveal(),
            $this->uploadLockService->reveal(),
            $uploadFileChunkOperationFactory->reveal(),
            $this->fileOperationFactory->reveal(),
            $this->s3Service->reveal(),
            $incrementalHashService->reveal(),
            $this->fileSizeLimitService->reveal(),
            $fileService->reveal(),
            $client400BadContentExceptionFactory->reveal(),
            $this->client404NotFoundExceptionFactory->reveal(),
            $client409ConflictExceptionFactory->reveal(),
            $this->prophesize(Client410GoneExceptionFactory::class)->reveal(),
        );
    }

    private function patch(PatchUploadController $controller, ?Request $request = null): void
    {
        $controller->patchUpload(self::UPLOAD_ID, $request ?? new Request());
    }

    /**
     * @param class-string<ProblemJsonException> $exceptionClass
     */
    private function assertRejectedWithDetail(PatchUploadController $controller, string $exceptionClass, string $expectedDetailPart): void
    {
        try {
            $this->patch($controller);
        } catch (ProblemJsonException $exception) {
            $this->assertInstanceOf($exceptionClass, $exception);
            $this->assertStringContainsString($expectedDetailPart, $exception->getDetail());

            return;
        }
        $this->fail('Expected the chunk to be rejected.');
    }

    public function testDeclaredIntermediateChunkBelowMinimumIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(self::MIN_CHUNK_SIZE - 1);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client400BadContentException::class, 'at least');
    }

    public function testDeclaredEmptyIntermediateChunkIsANoOpReportingCurrentState(): void
    {
        $controller = $this->createController(0, uploadOffset: 200, s3ChunkLength: 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->shouldNotBeCalled();
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();

        $response = $controller->patchUpload(self::UPLOAD_ID, new Request());

        $this->assertInstanceOf(NoContentResponse::class, $response);
    }

    public function testUndeclaredEmptyIntermediateChunkIsANoOpReportingCurrentState(): void
    {
        $controller = $this->createController(null, s3ChunkLength: 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->shouldNotBeCalled();

        $this->assertInstanceOf(NoContentResponse::class, $controller->patchUpload(self::UPLOAD_ID, new Request()));
    }

    /**
     * @return array<string, array{int, bool}>
     */
    public static function intermediateChunkSizeProvider(): array
    {
        return [
            'one byte' => [1, false],
            'minimum minus one' => [self::MIN_CHUNK_SIZE - 1, false],
            'minimum' => [self::MIN_CHUNK_SIZE, true],
        ];
    }

    #[DataProvider('intermediateChunkSizeProvider')]
    public function testIntermediateChunkSizesAroundMinimum(int $size, bool $isAccepted): void
    {
        $controller = $this->createController($size, s3ChunkLength: $size);
        if ($isAccepted) {
            $this->s3Service->uploadFileChunk(Argument::any())->shouldBeCalledOnce()->willReturn($size);
            $this->uploadService->appendChunkIfOffsetMatches(Argument::cetera())->shouldBeCalledOnce()->willReturn(true);
            $this->assertInstanceOf(NoContentResponse::class, $this->runPatch($controller));

            return;
        }
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->assertRejectedWithDetail($controller, Client400BadContentException::class, 'at least');
    }

    private function runPatch(PatchUploadController $controller): mixed
    {
        return $controller->patchUpload(self::UPLOAD_ID, new Request());
    }

    public function testCompletingUploadShorterThanDeclaredLengthIsRejectedAndDiscarded(): void
    {
        $controller = $this->createController(300, isUploadComplete: true, uploadOffset: 200, uploadLength: 600, s3ChunkLength: 300);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldBeCalledOnce();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'defined upload length');
    }

    public function testCompletingUploadLongerThanDeclaredLengthIsRejectedAndDiscarded(): void
    {
        $controller = $this->createController(500, isUploadComplete: true, uploadOffset: 200, uploadLength: 600, s3ChunkLength: 500);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldBeCalledOnce();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'exceeds defined upload length');
    }

    public function testCompletingUploadWithoutDeclaredContentLengthLongerThanDeclaredLengthIsDiscardedAfterS3(): void
    {
        $controller = $this->createController(null, isUploadComplete: true, uploadOffset: 200, uploadLength: 600, s3ChunkLength: 500);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldBeCalledOnce()->willReturn(500);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldBeCalledOnce();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'exceeds defined upload length');
    }

    public function testIntermediateChunkExceedingDeclaredLengthKeepsUpload(): void
    {
        $controller = $this->createController(500, uploadOffset: 200, uploadLength: 600);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'exceeds defined upload length');
    }

    public function testLostAccessToTargetCancelsUploadAndAnswersNotFound(): void
    {
        $controller = $this->createController(500, hasAccess: false);
        $this->uploadCancellationService->cancelUpload(Argument::any())->shouldBeCalledOnce()->willReturn(true);
        $this->uploadLockService->acquire(Argument::any())->shouldNotBeCalled();
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client404NotFoundException::class);
        $this->patch($controller);
    }

    public function testDeclaredChunkAboveMaximumIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(self::MAX_CHUNK_SIZE + 1);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client400BadContentException::class, 'at most');
    }

    public function testDeclaredFinalChunkAboveMaximumIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(self::MAX_CHUNK_SIZE + 1, isUploadComplete: true);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
        $this->patch($controller);
    }

    public function testDeclaredChunkExceedingUploadLengthIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(500, uploadOffset: 200, uploadLength: 600);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $this->patch($controller);
    }

    public function testDeclaredChunkExceedingFileSizeLimitIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(500, uploadOffset: 200);
        $this->fileSizeLimitService->assertWithinMaxFileSize(700)->shouldBeCalledOnce()->willThrow(new Client400BadContentException('type'));
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
        $this->patch($controller);
    }

    /**
     * @return array<string, array{int, bool, string}>
     */
    public static function undeclaredLengthProvider(): array
    {
        return [
            'intermediate chunk below minimum' => [self::MIN_CHUNK_SIZE - 1, false, 'at least'],
            'chunk above maximum' => [self::MAX_CHUNK_SIZE + 1, false, 'at most'],
            'final chunk above maximum' => [self::MAX_CHUNK_SIZE + 1, true, 'at most'],
        ];
    }

    /**
     * Without a `Content-Length` header (e.g. chunked transfer encoding) the length is only known after the upload,
     * so the same checks still have to reject the chunk afterwards.
     */
    #[DataProvider('undeclaredLengthProvider')]
    public function testChunkWithoutDeclaredLengthIsStillRejectedAfterS3(int $s3ChunkLength, bool $isUploadComplete, string $message): void
    {
        $controller = $this->createController(null, isUploadComplete: $isUploadComplete, s3ChunkLength: $s3ChunkLength);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldBeCalledOnce()->willReturn($s3ChunkLength);

        $this->assertRejectedWithDetail($controller, Client400BadContentException::class, $message);
    }

    public function testHeldLockIsAnsweredWithConflictBeforeAnythingIsRead(): void
    {
        $controller = $this->createController(500);
        $this->uploadLockService->acquire(Argument::any())->willReturn(null);
        $this->uploadLockService->release(Argument::cetera())->shouldNotBeCalled();
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'Another request');
    }

    public function testLockIsReleasedWhenAppendFails(): void
    {
        $controller = $this->createController(self::MAX_CHUNK_SIZE + 1);
        $this->uploadLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->expectException(Client400BadContentException::class);
        $this->patch($controller);
    }

    public function testConcurrentModificationIsAnsweredWithConflictAndDeletesOwnChunk(): void
    {
        $controller = $this->createController(500, offsetMatches: false);
        $this->s3Service->deleteFile(Argument::any())->shouldBeCalledOnce();
        $this->fileOperationFactory->createFileOperationFromUpload(Argument::any(), 2, 'aaaaaaaaaaaaaaaa')->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'modified by another request');
    }

    public function testConcurrentModificationOfFinalChunkDoesNotFinalize(): void
    {
        $controller = $this->createController(500, isUploadComplete: true, offsetMatches: false);
        $this->s3Service->deleteFile(Argument::any())->shouldBeCalledOnce();
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();

        $this->assertRejectedWithDetail($controller, Client409ConflictException::class, 'modified by another request');
    }

    public function testChunkRejectedAfterS3UploadDeletesOwnChunk(): void
    {
        $controller = $this->createController(null, s3ChunkLength: self::MAX_CHUNK_SIZE + 1);
        $this->s3Service->deleteFile(Argument::any())->shouldBeCalledOnce();

        $this->assertRejectedWithDetail($controller, Client400BadContentException::class, 'at most');
    }

    public function testFinalChunkIsFinalizedWithReprDigestOnly(): void
    {
        $controller = $this->createController(500, isUploadComplete: true);
        $this->uploadFinalizationService->finalize(Argument::any(), Argument::type('string'), 'sha-256=:repr:')->shouldBeCalledOnce();

        $request = new Request();
        $request->headers->set('Repr-Digest', 'sha-256=:repr:');
        $request->headers->set('Content-Digest', 'sha-256=:content:');
        $this->patch($controller, $request);
    }

    public function testContentDigestIsIgnoredOnFinalChunk(): void
    {
        $controller = $this->createController(500, isUploadComplete: true);
        $this->uploadFinalizationService->finalize(Argument::any(), Argument::type('string'), null)->shouldBeCalledOnce();

        $request = new Request();
        $request->headers->set('Content-Digest', 'sha-256=:content:');
        $this->patch($controller, $request);
    }

    public function testIntermediateChunkIsNotFinalized(): void
    {
        $controller = $this->createController(500);
        $this->uploadFinalizationService->finalize(Argument::cetera())->shouldNotBeCalled();
        $this->s3Service->deleteFile(Argument::any())->shouldNotBeCalled();

        $this->patch($controller);
    }
}
