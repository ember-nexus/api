<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\Request\ResumableUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Security\AuthProvider;
use App\Service\DigestService;
use App\Service\ElementManager;
use App\Service\FileService;
use App\Service\FileSizeLimitService;
use App\Service\IncrementalHashService;
use App\Service\S3Service;
use App\Service\UploadBodyLimitService;
use App\Service\UploadCreationService;
use App\Service\UploadService;
use App\Type\Response\CreatedResponse;
use App\Type\Response\NoContentResponse;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Small]
#[CoversClass(UploadCreationService::class)]
class UploadCreationServiceTest extends TestCase
{
    use ProphecyTrait;

    private const int MAX_FILE_SIZE = 1000;
    private const int MIN_CHUNK_SIZE = 10;
    private const int MAX_CHUNK_SIZE = 100;

    private UuidInterface $elementId;
    /** @var ObjectProphecy<NodeElementInterface> */
    private ObjectProphecy $element;
    /** @var ObjectProphecy<ResumableUploadRequestInterface> */
    private ObjectProphecy $resumableUploadRequest;
    /** @var ObjectProphecy<S3Service> */
    private ObjectProphecy $s3Service;
    /** @var ObjectProphecy<ElementManager> */
    private ObjectProphecy $elementManager;
    /** @var ObjectProphecy<UploadService> */
    private ObjectProphecy $uploadService;
    /** @var ObjectProphecy<EventDispatcherInterface> */
    private ObjectProphecy $eventDispatcher;
    private UploadCreationService $service;
    /** @var ObjectProphecy<Client409ConflictExceptionFactory> */
    private ObjectProphecy $conflictFactory;

    protected function setUp(): void
    {
        $this->elementId = Uuid::uuid4();
        $this->element = $this->prophesize(NodeElementInterface::class);
        $this->resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $this->resumableUploadRequest->getElementId()->willReturn($this->elementId);
        $this->resumableUploadRequest->getExtension()->willReturn('bin');
        $this->resumableUploadRequest->getUploadLength()->willReturn(null);
        $this->resumableUploadRequest->getContentLength()->willReturn(null);

        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->getElementOrFail(Argument::any())->willReturn($this->element->reveal());
        $this->s3Service = $this->prophesize(S3Service::class);
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->elementManager->merge(Argument::any())->willReturn($this->elementManager->reveal());
        $this->elementManager->flush()->willReturn($this->elementManager->reveal());
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->eventDispatcher->dispatch(Argument::any())->will(fn (array $args) => $args[0]);

        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getFileMaxFileSizeInBytes()->willReturn(self::MAX_FILE_SIZE);
        $configuration->getFileUploadMinChunkSizeInBytes()->willReturn(self::MIN_CHUNK_SIZE);
        $configuration->getFileUploadMaxChunkSizeInBytes()->willReturn(self::MAX_CHUNK_SIZE);
        $configuration->getFileUploadExpiresInSecondsAfterFirstRequest()->willReturn(3600);
        $configuration = $configuration->reveal();

        $this->conflictFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $this->conflictFactory
            ->createFromDetail(Argument::cetera())
            ->will(fn (array $args) => new Client409ConflictException('conflict', detail: $args[0]));

        $badContentFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $badContentFactory
            ->createFromDetail(Argument::type('string'))
            ->will(fn (array $args) => new Client400BadContentException('bad-content', detail: $args[0]));
        $badContentFactory = $badContentFactory->reveal();

        $fileService = $this->prophesize(FileService::class);
        $fileService->generateUploadChunkId()->willReturn('0123456789abcdef');

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn(Uuid::uuid4());

        $requestFactory = $this->prophesize(ResumableUploadRequestFactory::class);
        $requestFactory
            ->createResumableUploadRequestFromRequest(Argument::any(), Argument::any())
            ->willReturn($this->resumableUploadRequest->reveal());

        $uploadFileOperationFactory = $this->prophesize(UploadFileOperationFactory::class);
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->willReturn(5);
        $uploadFileOperation->getMimeType()->willReturn('application/octet-stream');
        $uploadFileOperationFactory
            ->createUploadFileOperationFromResumableUploadRequest(Argument::any())
            ->willReturn($uploadFileOperation->reveal());

        $chunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $chunkOperationFactory
            ->createUploadFileChunkOperationFromResumableUploadRequest(Argument::cetera())
            ->willReturn($this->prophesize(UploadFileChunkOperationInterface::class)->reveal());

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('/upload/x');

        $this->service = new UploadCreationService(
            $authProvider->reveal(),
            $configuration,
            $this->s3Service->reveal(),
            new IncrementalHashService($this->prophesize(Client409ConflictExceptionFactory::class)->reveal()),
            new DigestService(),
            $this->elementManager->reveal(),
            $uploadFileOperationFactory->reveal(),
            $chunkOperationFactory->reveal(),
            $requestFactory->reveal(),
            $this->eventDispatcher->reveal(),
            new NoContentResponseFactory($configuration),
            $urlGenerator->reveal(),
            $this->uploadService->reveal(),
            new FileSizeLimitService($configuration, $badContentFactory),
            new UploadBodyLimitService($configuration, $badContentFactory, new Client408RequestTimeoutExceptionFactory($urlGenerator->reveal())),
            $fileService->reveal(),
            $badContentFactory,
            $this->conflictFactory->reveal(),
        );
    }

    /**
     * @return resource
     */
    private function contentResource(string $content = 'hello')
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        return $resource;
    }

    private function configureDirectUpload(?string $content = 'hello'): void
    {
        $this->resumableUploadRequest->isUploadComplete()->willReturn(true);
        $this->resumableUploadRequest->getContent()->willReturn($this->contentResource($content));
    }

    private function configureResumableUpload(?int $uploadLength, ?int $contentLength, bool $isComplete = false): void
    {
        $this->resumableUploadRequest->isUploadComplete()->willReturn($isComplete);
        $this->resumableUploadRequest->getUploadLength()->willReturn($uploadLength);
        $this->resumableUploadRequest->getContentLength()->willReturn($contentLength);
        $this->resumableUploadRequest->getContent()->willReturn($this->contentResource());
    }

    /**
     * @param array<string, string> $additionalHeaders
     */
    private function handle(?string $digestHeader = null, string $headerName = 'Repr-Digest', array $additionalHeaders = []): mixed
    {
        $request = new Request();
        if (null !== $digestHeader) {
            $request->headers->set($headerName, $digestHeader);
        }
        foreach ($additionalHeaders as $name => $value) {
            $request->headers->set($name, $value);
        }

        return $this->service->handleUploadCreationFromRequest($this->elementId, $request);
    }

    private function assertBadContentContaining(string $needle, callable $call): void
    {
        try {
            $call();
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString($needle, $exception->getDetail());

            return;
        }
        $this->fail('Expected Client400BadContentException.');
    }

    public function testDirectUploadWithoutDigestSucceeds(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty('hasFile', true)->shouldBeCalledOnce()->willReturn($this->element->reveal());
        $this->element->addProperty('file', Argument::that(
            fn (array $file) => 5 === $file['contentLength']
                && 'bin' === $file['extension']
                && hash('sha256', 'hello') === $file['hash']['sha256']
        ))->shouldBeCalledOnce()->willReturn($this->element->reveal());
        $this->elementManager->merge(Argument::any())->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());

        $this->assertInstanceOf(CreatedResponse::class, $this->handle());
    }

    public function testDirectUploadWithMatchingDigestSucceeds(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty(Argument::cetera())->shouldBeCalled()->willReturn($this->element->reveal());
        $digest = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'hello'));

        $this->assertInstanceOf(CreatedResponse::class, $this->handle($digest));
    }

    public function testDirectUploadAcceptsContentDigestHeader(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty(Argument::cetera())->shouldBeCalled()->willReturn($this->element->reveal());
        $digest = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'hello'));

        $this->assertInstanceOf(CreatedResponse::class, $this->handle($digest, 'Content-Digest'));
    }

    public function testDirectUploadWithBothMatchingDigestHeadersSucceeds(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty(Argument::cetera())->shouldBeCalled()->willReturn($this->element->reveal());
        $digest = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'hello'));

        $this->assertInstanceOf(CreatedResponse::class, $this->handle($digest, 'Repr-Digest', ['Content-Digest' => $digest]));
    }

    public function testDirectUploadWithCorrectReprAndWrongContentDigestIsRejected(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();
        $this->elementManager->merge(Argument::any())->shouldNotBeCalled();
        $correct = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'hello'));
        $wrong = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'something else'));

        $this->assertBadContentContaining(
            "'Content-Digest'",
            fn () => $this->handle($correct, 'Repr-Digest', ['Content-Digest' => $wrong])
        );
    }

    public function testDirectUploadWithCorrectContentAndWrongReprDigestIsRejected(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();
        $this->elementManager->merge(Argument::any())->shouldNotBeCalled();
        $correct = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'hello'));
        $wrong = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'something else'));

        $this->assertBadContentContaining(
            "'Repr-Digest'",
            fn () => $this->handle($wrong, 'Repr-Digest', ['Content-Digest' => $correct])
        );
    }

    public function testDirectUploadWithUnsupportedContentDigestAlgorithmIsRejected(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining(
            "'Content-Digest' header",
            fn () => $this->handle('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:', 'Content-Digest')
        );
    }

    public function testDirectUploadWithUnsupportedDigestAlgorithmIsRejected(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();
        $this->elementManager->merge(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining(
            'does not declare a supported digest algorithm',
            fn () => $this->handle('md5=:1B2M2Y8AsgTpgAmY7PhCfg==:')
        );
    }

    public function testDirectUploadWithMismatchingDigestIsRejected(): void
    {
        $this->configureDirectUpload();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();
        $this->elementManager->merge(Argument::any())->shouldNotBeCalled();
        $digest = (new DigestService())->formatDigestHeaderValue(hash('sha256', 'something else'));

        $this->assertBadContentContaining(
            'does not match the uploaded file',
            fn () => $this->handle($digest)
        );
    }

    public function testDirectUploadAboveMaxFileSizeIsRejectedBeforeReadingContent(): void
    {
        $this->resumableUploadRequest->isUploadComplete()->willReturn(true);
        $this->resumableUploadRequest->getContentLength()->willReturn(self::MAX_FILE_SIZE + 1);
        $this->resumableUploadRequest->getContent()->shouldNotBeCalled();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining('at most 1000 bytes long', fn () => $this->handle());
    }

    public function testDirectUploadExactlyAtMaxChunkSizeIsAccepted(): void
    {
        $this->configureDirectUpload();
        $this->resumableUploadRequest->getContentLength()->willReturn(self::MAX_CHUNK_SIZE);
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(self::MAX_CHUNK_SIZE);
        $this->element->addProperty(Argument::cetera())->shouldBeCalled()->willReturn($this->element->reveal());

        $this->assertInstanceOf(CreatedResponse::class, $this->handle());
    }

    public function testDirectUploadAboveMaxChunkSizeIsRejectedBeforeReadingContent(): void
    {
        $this->resumableUploadRequest->isUploadComplete()->willReturn(true);
        $this->resumableUploadRequest->getContentLength()->willReturn(self::MAX_CHUNK_SIZE + 1);
        $this->resumableUploadRequest->getContent()->shouldNotBeCalled();
        $this->s3Service->uploadFile(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining('at most 100 bytes long, got 101', fn () => $this->handle());
    }

    public function testDirectUploadWithoutContentLengthIsAcceptedInService(): void
    {
        // the stream itself is bound in the request factory, the service only checks a declared length
        $this->configureDirectUpload();
        $this->resumableUploadRequest->getContentLength()->willReturn(null);
        $this->s3Service->uploadFile(Argument::any())->shouldBeCalledOnce()->willReturn(5);
        $this->element->addProperty(Argument::cetera())->shouldBeCalled()->willReturn($this->element->reveal());

        $this->assertInstanceOf(CreatedResponse::class, $this->handle());
    }

    public function testUploadLengthAboveMaxFileSizeIsRejected(): void
    {
        $this->configureResumableUpload(self::MAX_FILE_SIZE + 1, 0);
        $this->uploadService->mergeUploadElement(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining('at most 1000 bytes long', fn () => $this->handle());
    }

    public function testResumableUploadWithoutContentCreatesEmptyUpload(): void
    {
        $this->configureResumableUpload(500, 0);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->mergeUploadElement(Argument::that(
            fn ($upload) => 0 === $upload->getUploadOffset()
                && 500 === $upload->getUploadLength()
                && 0 === $upload->getAlreadyUploadedChunks()
                && null === $upload->getHashState()
        ))->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());

        $response = $this->handle();

        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertSame('0', $response->headers->get('Upload-Offset'));
        $this->assertSame('/upload/x', $response->headers->get('Location'));
    }

    public function testResumableUploadWithValidFirstChunkStoresOffsetAndHashState(): void
    {
        $this->configureResumableUpload(500, 50);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldBeCalledOnce()->willReturn(50);
        $this->uploadService->mergeUploadElement(Argument::that(
            fn ($upload) => 50 === $upload->getUploadOffset()
                && 1 === $upload->getAlreadyUploadedChunks()
                && ['0123456789abcdef'] === $upload->getChunkIds()
                && null !== $upload->getHashState()
        ))->shouldBeCalledOnce();

        $this->assertSame('50', $this->handle()->headers->get('Upload-Offset'));
    }

    public function testFirstChunkBelowMinChunkSizeIsRejected(): void
    {
        $this->configureResumableUpload(500, 5);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn(5);
        $this->uploadService->mergeUploadElement(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining('at least 10 bytes long, got 5', fn () => $this->handle());
    }

    public function testFirstChunkAboveMaxChunkSizeIsRejected(): void
    {
        $this->configureResumableUpload(500, 101);
        $this->s3Service->uploadFileChunk(Argument::any())->willReturn(101);
        $this->uploadService->mergeUploadElement(Argument::any())->shouldNotBeCalled();

        $this->assertBadContentContaining('at most 100 bytes long, got 101', fn () => $this->handle());
    }

    public function testFirstChunkAtChunkSizeBoundariesIsAccepted(): void
    {
        foreach ([self::MIN_CHUNK_SIZE, self::MAX_CHUNK_SIZE] as $size) {
            $this->configureResumableUpload(500, $size);
            $this->s3Service->uploadFileChunk(Argument::any())->willReturn($size);
            $this->uploadService->mergeUploadElement(Argument::any())->shouldBeCalled();

            $this->assertSame((string) $size, $this->handle()->headers->get('Upload-Offset'));
        }
    }

    public function testResumableUploadWithEmptyBodyAndWithoutContentLengthCreatesEmptyUpload(): void
    {
        $this->resumableUploadRequest->isUploadComplete()->willReturn(false);
        $this->resumableUploadRequest->getUploadLength()->willReturn(500);
        $this->resumableUploadRequest->getContentLength()->willReturn(null);
        $this->resumableUploadRequest->getContent()->willReturn($this->contentResource(''));
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->mergeUploadElement(Argument::that(
            fn ($upload) => 0 === $upload->getUploadOffset()
                && 0 === $upload->getAlreadyUploadedChunks()
                && null === $upload->getHashState()
        ))->shouldBeCalledOnce();

        $this->assertSame('0', $this->handle()->headers->get('Upload-Offset'));
    }

    public function testFirstChunkExceedingUploadLengthIsRejectedBeforeS3(): void
    {
        $this->configureResumableUpload(40, 50);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldNotBeCalled();
        $this->uploadService->mergeUploadElement(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $this->handle();
    }

    public function testFirstChunkMatchingUploadLengthIsAccepted(): void
    {
        $this->configureResumableUpload(50, 50);
        $this->s3Service->uploadFileChunk(Argument::any())->shouldBeCalledOnce()->willReturn(50);
        $this->uploadService->mergeUploadElement(Argument::any())->shouldBeCalledOnce();

        $this->assertSame('50', $this->handle()->headers->get('Upload-Offset'));
    }
}
