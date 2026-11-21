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
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

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

    private function buildUpload(Uuid $id): ObjectProphecy
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn($id);
        $upload->getUploadOwner()->willReturn(Uuid::fromString('02IiNr0G8viOVKjTVopQ38JnV8PxJrftZpRV7'));
        $upload->getUploadTarget()->willReturn(Uuid::fromString('02IiNr0G8viOVKjboyDliyOxJkyYo8OHX5lnv'));

        return $upload;
    }

    public function testLockedUploadIsNotCancelled(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkgIBMZ7h4DqjROiI7NBCOGi');
        $upload = $this->buildUpload($id);
        $this->uploadLockService->acquire($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->assertFalse($this->service->cancelUpload($upload->reveal()));
    }

    public function testCancelUploadReloadsUploadUnderLockAndDeletesChunksAndNode(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkgIBMZ7h4DqjROiI7NBCOGi');
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
        $id = Uuid::fromString('02IiNr0G8viOVKkgIBMZ7h4DqjROiI7NBCOGi');
        $upload = $this->buildUpload($id);
        $this->elementManager->getElement($id)->willReturn(null);
        $this->uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $this->uploadLockService->release($id, 'token')->shouldBeCalledOnce();

        $this->assertTrue($this->service->cancelUpload($upload->reveal()));
    }
}
