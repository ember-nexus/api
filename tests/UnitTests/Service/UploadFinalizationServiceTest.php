<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\UploadInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use App\Service\DigestService;
use App\Service\ElementManager;
use App\Service\FileSizeLimitService;
use App\Service\S3Service;
use App\Service\UploadFinalizationService;
use App\Service\UploadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
    private UploadFinalizationService $service;

    protected function setUp(): void
    {
        $this->targetId = Uuid::uuid4();
        $this->upload = $this->prophesize(UploadInterface::class);
        $this->upload->getUploadTarget()->willReturn($this->targetId);
        $this->upload->getUploadOffset()->willReturn(5);
        $this->upload->getExtension()->willReturn('txt');

        $this->element = $this->prophesize(NodeElementInterface::class);
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->getElementOrFail(Argument::any())->willReturn($this->element->reveal());
        $this->elementManager->merge(Argument::any())->willReturn($this->elementManager->reveal());
        $this->elementManager->delete(Argument::any())->willReturn($this->elementManager->reveal());
        $this->elementManager->flush()->willReturn($this->elementManager->reveal());

        $mergeOperation = $this->prophesize(MergeFileChunksOperationInterface::class)->reveal();
        $mergeFactory = $this->prophesize(MergeFileChunksOperationFactory::class);
        $mergeFactory->createMergeFileOperationFromUpload(Argument::any())->willReturn($mergeOperation);

        $this->s3Service = $this->prophesize(S3Service::class);
        $this->s3Service->mergeFileChunks(Argument::any())->willReturn(5);
        $this->s3Service->getMimeTypeFromMergeFileChunksOperation(Argument::any())->willReturn('text/plain');
        $this->s3Service->deleteFileChunks(Argument::any())->will(function () {});

        $this->uploadService = $this->prophesize(UploadService::class);
        $this->fileSizeLimitService = $this->prophesize(FileSizeLimitService::class);
        $this->eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $this->eventDispatcher->dispatch(Argument::any())->will(fn (array $args) => $args[0]);

        $badContentFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $badContentFactory->createFromDetail(Argument::type('string'))->will(
            fn (array $args) => new Client400BadContentException('bad-content', detail: $args[0])
        );

        $this->service = new UploadFinalizationService(
            $this->elementManager->reveal(),
            $this->eventDispatcher->reveal(),
            $mergeFactory->reveal(),
            $this->s3Service->reveal(),
            $this->uploadService->reveal(),
            new DigestService(),
            $this->fileSizeLimitService->reveal(),
            $badContentFactory->reveal(),
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

        $this->service->finalize($this->upload->reveal(), self::HASH);
    }

    public function testFinalizeWithMatchingReprDigestMergesChunks(): void
    {
        $this->expectFileToBeMerged();

        $this->service->finalize($this->upload->reveal(), self::HASH, $this->digestHeader());
    }

    public function testFinalizeWithMismatchingDigestDiscardsUpload(): void
    {
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), self::HASH, $this->digestHeader(hash('sha256', 'other')));
            $this->fail('Expected digest mismatch to be rejected.');
        } catch (Client400BadContentException $exception) {
            $this->assertStringContainsString('does not match', $exception->getDetail());
        }
    }

    public function testFinalizeWithUnsupportedDigestAlgorithmDiscardsUpload(): void
    {
        $this->expectUploadToBeDiscarded();

        try {
            $this->service->finalize($this->upload->reveal(), self::HASH, 'md5=:AAAA:');
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
        $this->service->finalize($this->upload->reveal(), self::HASH);
    }
}
