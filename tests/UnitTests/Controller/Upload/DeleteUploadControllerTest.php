<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Controller\Upload\DeleteUploadController;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\UploadLockService;
use App\Service\UploadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(DeleteUploadController::class)]
class DeleteUploadControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';

    private function buildController(?string $lockToken, $uploadService, $lockService): DeleteUploadController
    {
        $userId = Uuid::uuid4();
        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $upload->getUploadOwner()->willReturn($userId);
        $upload->getUploadTarget()->willReturn(Uuid::uuid4());
        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement(Argument::any())->willReturn($upload->reveal());
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::any())->willReturn($this->prophesize(NodeElementInterface::class)->reveal());
        $elementManager->flush()->willReturn($elementManager->reveal());
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);
        $lockService->acquire(Argument::any())->willReturn($lockToken);
        $conflictFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $conflictFactory->createFromDetail(Argument::cetera())->will(fn ($args) => new Client409ConflictException('type', detail: $args[0]));

        return new DeleteUploadController(
            $authProvider->reveal(),
            $elementManager->reveal(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $uploadFactory->reveal(),
            $uploadService->reveal(),
            $lockService->reveal(),
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $conflictFactory->reveal(),
        );
    }

    public function testHeldLockIsAnsweredWithConflict(): void
    {
        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUploadAndChunks(Argument::any())->shouldNotBeCalled();
        $lockService = $this->prophesize(UploadLockService::class);
        $lockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $this->buildController(null, $uploadService, $lockService)->deleteUpload(self::UPLOAD_ID);
    }

    public function testUploadIsDeletedUnderLockAndLockIsReleased(): void
    {
        $uploadService = $this->prophesize(UploadService::class);
        $uploadService->deleteUploadAndChunks(Argument::any())->shouldBeCalledOnce();
        $lockService = $this->prophesize(UploadLockService::class);
        $lockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->buildController('token', $uploadService, $lockService)->deleteUpload(self::UPLOAD_ID);
    }
}
