<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Exception\Client404NotFoundException;
use App\Exception\Client410GoneException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\UploadAccessService;
use App\Service\UploadCancellationService;
use App\Service\UploadConsistencyService;
use App\Type\AccessType;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(UploadAccessService::class)]
class UploadAccessServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';

    private ObjectProphecy $uploadCancellationService;
    private ObjectProphecy $uploadConsistencyService;
    private ObjectProphecy $uploadFactory;
    private ObjectProphecy $upload;
    private ObjectProphecy $accessChecker;
    private NodeElementInterface $uploadElement;

    private function createService(string $expires = '+1 hour', bool $isOwner = true, bool $hasAccess = true): UploadAccessService
    {
        $userId = Uuid::uuid4();
        $targetId = Uuid::uuid4();

        $this->upload = $this->prophesize(UploadInterface::class);
        $this->upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $this->upload->getUploadOwner()->willReturn($isOwner ? $userId : Uuid::uuid4());
        $this->upload->getUploadTarget()->willReturn($targetId);
        $this->upload->getExpires()->willReturn(new DateTime($expires));

        $this->uploadElement = $this->prophesize(NodeElementInterface::class)->reveal();
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::any())->willReturn($this->uploadElement);
        $this->uploadFactory = $this->prophesize(UploadFactory::class);
        $this->uploadFactory->createUploadFromElement($this->uploadElement)->willReturn($this->upload->reveal());

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);
        $this->accessChecker = $this->prophesize(AccessChecker::class);
        $this->accessChecker->hasAccessToElement($userId, $targetId, AccessType::UPDATE)->willReturn($hasAccess);

        $this->uploadCancellationService = $this->prophesize(UploadCancellationService::class);
        $this->uploadConsistencyService = $this->prophesize(UploadConsistencyService::class);

        $notFound = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $notFound->createFromTemplate()->will(fn () => new Client404NotFoundException('not found'));
        $gone = $this->prophesize(Client410GoneExceptionFactory::class);
        $gone->createFromTemplate()->will(fn () => new Client410GoneException('gone'));

        return new UploadAccessService(
            $authProvider->reveal(),
            $this->accessChecker->reveal(),
            $elementManager->reveal(),
            $this->uploadFactory->reveal(),
            $this->uploadCancellationService->reveal(),
            $this->uploadConsistencyService->reveal(),
            $notFound->reveal(),
            $gone->reveal(),
        );
    }

    public function testOwnerWithAccessGetsConsistentUpload(): void
    {
        $service = $this->createService();
        $this->uploadConsistencyService->assertConsistent($this->uploadElement, $this->upload->reveal())->shouldBeCalledOnce();

        $this->assertSame($this->upload->reveal(), $service->loadAuthorizedUpload(self::UPLOAD_ID));
    }

    public function testElementWhichIsNoUploadIsNotFound(): void
    {
        $service = $this->createService();
        $this->uploadFactory->createUploadFromElement($this->uploadElement)->willThrow(new Exception('no upload'));

        $this->expectException(Client404NotFoundException::class);
        $service->loadAuthorizedUpload(self::UPLOAD_ID);
    }

    public function testUploadOfSomebodyElseIsNotFound(): void
    {
        $service = $this->createService(isOwner: false);
        $this->accessChecker->hasAccessToElement(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client404NotFoundException::class);
        $service->loadAuthorizedUpload(self::UPLOAD_ID);
    }

    public function testLostAccessCancelsUploadAndIsNotFound(): void
    {
        $service = $this->createService(hasAccess: false);
        $this->uploadCancellationService->cancelUpload($this->upload->reveal())->shouldBeCalledOnce();
        $this->uploadConsistencyService->assertConsistent(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client404NotFoundException::class);
        $service->loadAuthorizedUpload(self::UPLOAD_ID);
    }

    public function testExpiredUploadIsGone(): void
    {
        $service = $this->createService('-1 hour');
        $this->uploadConsistencyService->assertConsistent(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client410GoneException::class);
        $service->loadAuthorizedUpload(self::UPLOAD_ID);
    }
}
