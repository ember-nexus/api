<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\UploadInterface;
use App\Factory\Type\UploadFactory;
use App\Service\ElementManager;
use App\Service\UploadCancellationService;
use App\Service\UploadLockService;
use App\Service\UploadService;
use App\Type\NodeElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;

#[Small]
#[CoversClass(UploadCancellationService::class)]
class UploadCancellationServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $elementManager;
    private ObjectProphecy $uploadFactory;
    private ObjectProphecy $uploadService;
    private ObjectProphecy $uploadLockService;
    private UploadCancellationService $service;

    protected function setUp(): void
    {
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->flush()->willReturn($this->elementManager->reveal());
        $this->uploadFactory = $this->prophesize(UploadFactory::class);
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadLockService = $this->prophesize(UploadLockService::class);
        $this->uploadLockService->acquire(Argument::any())->willReturn('token');
        $this->uploadLockService->release(Argument::cetera())->will(function () {});

        $this->service = new UploadCancellationService(
            $this->elementManager->reveal(),
            $this->uploadFactory->reveal(),
            $this->uploadService->reveal(),
            $this->uploadLockService->reveal(),
        );
    }

    private function buildUpload(UuidInterface $id): ObjectProphecy
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn($id);
        $upload->getUploadOwner()->willReturn(UuidV4::fromString('11111111-1111-4111-8111-111111111111'));
        $upload->getUploadTarget()->willReturn(UuidV4::fromString('22222222-2222-4222-8222-222222222222'));

        return $upload;
    }

    public function testLockedUploadIsNotCancelled(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $this->uploadLockService->acquire($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($this->service->cancelUpload($upload->reveal()));
    }

    public function testCancelUploadReloadsUploadUnderLockAndDeletesChunksAndNode(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $freshUpload = $this->buildUpload($id);
        $element = (new NodeElement())->setId($id)->setLabel('Upload');
        $this->elementManager->getElement($id)->willReturn($element);
        $this->uploadFactory->createUploadFromElement($element)->willReturn($freshUpload->reveal());
        $this->uploadService->deleteUploadAndChunks($freshUpload->reveal())->shouldBeCalledOnce();
        $this->elementManager->flush()->shouldBeCalledOnce()->willReturn($this->elementManager->reveal());
        $this->uploadLockService->release($id, 'token')->shouldBeCalledOnce();

        $this->assertTrue($this->service->cancelUpload($upload->reveal()));
    }

    public function testCancelUploadOfAlreadyDeletedUploadSucceedsWithoutDeleting(): void
    {
        $id = UuidV4::fromString('aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
        $upload = $this->buildUpload($id);
        $this->elementManager->getElement($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release($id, 'token')->shouldBeCalledOnce();

        $this->assertTrue($this->service->cancelUpload($upload->reveal()));
    }
}
