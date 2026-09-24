<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\NodeElementInterface;
use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\UploadInterface;
use App\Controller\Upload\PatchUploadController;
use App\Exception\Client400BadContentException;
use App\Exception\Client409ConflictException;
use App\Exception\ProblemJsonException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\DigestService;
use App\Service\ElementManager;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadService;
use App\Type\AccessType;
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
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @param int|null $declaredContentLength `Content-Length` of the request, null if the header is missing
     */
    private function createController(
        ?int $declaredContentLength,
        bool $isUploadComplete = false,
        int $uploadOffset = 0,
        ?int $uploadLength = null,
        int $s3ChunkLength = 500,
    ): PatchUploadController {
        $userId = Uuid::fromString(self::USER_ID);
        $targetId = Uuid::fromString(self::TARGET_ID);

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getUploadOwner()->willReturn($userId);
        $upload->getUploadTarget()->willReturn($targetId);
        $upload->getExpires()->willReturn(new DateTime('+1 day'));
        $upload->getUploadOffset()->willReturn($uploadOffset);
        $upload->getUploadLength()->willReturn($uploadLength);
        $upload->getHashState()->willReturn(null);

        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement(Argument::any())->willReturn($upload->reveal());

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::any())->willReturn($this->prophesize(NodeElementInterface::class)->reveal());

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement($userId, $targetId, AccessType::UPDATE)->willReturn(true);

        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileUploadMinChunkSizeInBytes()->willReturn(self::MIN_CHUNK_SIZE);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn(self::MAX_CHUNK_SIZE);

        $partialUploadRequest = $this->prophesize(PartialUploadRequestInterface::class);
        $partialUploadRequest->getUploadOffset()->willReturn($uploadOffset);
        $partialUploadRequest->getContentLength()->willReturn($declaredContentLength);
        $partialUploadRequest->isUploadComplete()->willReturn($isUploadComplete);
        $resource = fopen('php://memory', 'r+');
        $partialUploadRequest->getContent()->willReturn($resource);
        $partialUploadRequestFactory = $this->prophesize(PartialUploadRequestFactory::class);
        $partialUploadRequestFactory->createPartialUploadRequestFromRequest(Argument::any())->willReturn($partialUploadRequest->reveal());

        // reaching S3 means the chunk was not rejected beforehand; the real length is only known afterwards
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn($s3ChunkLength);

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory
            ->createUploadFileChunkOperationFromPartialUploadRequest(Argument::cetera())
            ->willReturn($this->prophesize(UploadFileChunkOperationInterface::class)->reveal());

        $incrementalHashService = $this->prophesize(IncrementalHashService::class);
        $incrementalHashService->createContext(Argument::any())->willReturn(hash_init('sha256'));
        $incrementalHashService->updateFromResource(Argument::cetera())->will(function () {});

        $this->fileSizeLimitService = $this->prophesize(FileSizeLimitService::class);

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
            $accessChecker->reveal(),
            $elementManager->reveal(),
            $configuration->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            $partialUploadRequestFactory->reveal(),
            $this->prophesize(NoContentResponseFactory::class)->reveal(),
            $uploadFactory->reveal(),
            $this->prophesize(UploadService::class)->reveal(),
            $uploadFileChunkOperationFactory->reveal(),
            $this->prophesize(MergeFileChunksOperationFactory::class)->reveal(),
            $this->s3Service->reveal(),
            $incrementalHashService->reveal(),
            $this->prophesize(DigestService::class)->reveal(),
            $this->fileSizeLimitService->reveal(),
            $client400BadContentExceptionFactory->reveal(),
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $client409ConflictExceptionFactory->reveal(),
            $this->prophesize(Client410GoneExceptionFactory::class)->reveal(),
        );
    }

    private function patch(PatchUploadController $controller): void
    {
        $controller->patchUpload(self::UPLOAD_ID, new Request());
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

    public function testDeclaredEmptyIntermediateChunkIsRejectedBeforeS3(): void
    {
        $controller = $this->createController(0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client400BadContentException::class);
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
}
