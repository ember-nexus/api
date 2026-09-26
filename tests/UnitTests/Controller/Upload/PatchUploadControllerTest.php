<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\UploadInterface;
use App\Controller\Upload\PatchUploadController;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\Request\PartialUploadRequestFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Service\UploadAccessService;
use App\Service\UploadAppendService;
use App\Service\UploadLockService;
use App\Type\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(PatchUploadController::class)]
class PatchUploadControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';

    private ObjectProphecy $uploadAccessService;
    private ObjectProphecy $uploadAppendService;
    private ObjectProphecy $uploadLockService;
    private ObjectProphecy $noContentResponseFactory;
    private UploadInterface $upload;
    private PartialUploadRequestInterface $partialUploadRequest;

    private function createController(?string $lockToken = 'token'): PatchUploadController
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $this->upload = $upload->reveal();
        $this->partialUploadRequest = $this->prophesize(PartialUploadRequestInterface::class)->reveal();

        $this->uploadAccessService = $this->prophesize(UploadAccessService::class);
        $this->uploadAccessService->loadAuthorizedUpload(self::UPLOAD_ID)->willReturn($this->upload);
        $this->uploadAppendService = $this->prophesize(UploadAppendService::class);
        $this->uploadLockService = $this->prophesize(UploadLockService::class);
        $this->uploadLockService->acquire(Argument::any())->willReturn($lockToken);
        $partialUploadRequestFactory = $this->prophesize(PartialUploadRequestFactory::class);
        $partialUploadRequestFactory->createPartialUploadRequestFromRequest(Argument::any())->willReturn($this->partialUploadRequest);
        $this->noContentResponseFactory = $this->prophesize(NoContentResponseFactory::class);
        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::cetera())->will(
            fn ($args) => new Client409ConflictException('type', detail: $args[0])
        );

        return new PatchUploadController(
            $this->uploadAccessService->reveal(),
            $this->uploadAppendService->reveal(),
            $this->uploadLockService->reveal(),
            $partialUploadRequestFactory->reveal(),
            $this->noContentResponseFactory->reveal(),
            $client409ConflictExceptionFactory->reveal(),
        );
    }

    public function testAppendsUnderLockAndReportsTheStateAfterwards(): void
    {
        $controller = $this->createController();
        $nextUpload = $this->prophesize(UploadInterface::class);
        $nextUpload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $response = new NoContentResponse();

        // the upload is loaded again once the lock is held, as the state may have changed while waiting
        $this->uploadAccessService->loadAuthorizedUpload(self::UPLOAD_ID)->shouldBeCalledTimes(2);
        $this->uploadAppendService->append($this->upload, $this->partialUploadRequest, 'sha-256=:abc=:')->willReturn($nextUpload->reveal())->shouldBeCalledOnce();
        $this->noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload($nextUpload->reveal())->willReturn($response);
        $this->uploadLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $request = new Request();
        $request->headers->set('Repr-Digest', 'sha-256=:abc=:');

        $this->assertSame($response, $controller->patchUpload(self::UPLOAD_ID, $request));
    }

    public function testHeldLockIsAnsweredWithConflictBeforeAnythingIsAppended(): void
    {
        $controller = $this->createController(null);
        $this->uploadAppendService->append(Argument::cetera())->shouldNotBeCalled();
        $this->uploadLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $controller->patchUpload(self::UPLOAD_ID, new Request());
    }

    public function testLockIsReleasedWhenAppendFails(): void
    {
        $controller = $this->createController();
        $this->uploadAppendService->append(Argument::cetera())->willThrow(new RuntimeException('failed'));
        $this->uploadLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->expectException(RuntimeException::class);
        $controller->patchUpload(self::UPLOAD_ID, new Request());
    }

    public function testLockIsReleasedWhenTheUploadCanNotBeLoadedAnymore(): void
    {
        $controller = $this->createController();
        // the first load before the lock succeeds, the second one inside of it fails
        $calls = 0;
        $upload = $this->upload;
        $this->uploadAccessService->loadAuthorizedUpload(self::UPLOAD_ID)->will(function () use (&$calls, $upload) {
            if (1 === ++$calls) {
                return $upload;
            }

            throw new RuntimeException('second load failed');
        });
        $this->uploadLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();
        $this->uploadAppendService->append(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(RuntimeException::class);
        $controller->patchUpload(self::UPLOAD_ID, new Request());
    }
}
