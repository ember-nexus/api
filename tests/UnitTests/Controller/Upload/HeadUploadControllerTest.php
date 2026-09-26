<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\Upload;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Controller\Upload\HeadUploadController;
use App\Exception\Client410GoneException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client410GoneExceptionFactory;
use App\Factory\Type\Response\NoContentResponseFactory;
use App\Factory\Type\UploadFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\UploadCancellationService;
use App\Service\UploadConsistencyService;
use App\Type\AccessType;
use App\Type\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;

#[Small]
#[CoversClass(HeadUploadController::class)]
class HeadUploadControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string UPLOAD_ID = '224a787e-3b32-4822-8697-61047175505d';

    private ObjectProphecy $uploadConsistencyService;

    private function buildController(string $expires): HeadUploadController
    {
        $userId = Uuid::uuid4();
        $targetId = Uuid::uuid4();

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->willReturn(Uuid::fromString(self::UPLOAD_ID));
        $upload->getUploadOwner()->willReturn($userId);
        $upload->getUploadTarget()->willReturn($targetId);
        $upload->getExpires()->willReturn(new DateTime($expires));

        $uploadElement = $this->prophesize(NodeElementInterface::class)->reveal();
        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::any())->willReturn($uploadElement);
        $uploadFactory = $this->prophesize(UploadFactory::class);
        $uploadFactory->createUploadFromElement($uploadElement)->willReturn($upload->reveal());

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn($userId);
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement($userId, $targetId, AccessType::UPDATE)->willReturn(true);

        $noContentResponseFactory = $this->prophesize(NoContentResponseFactory::class);
        $noContentResponseFactory->createNoContentResponseWithResumableUploadHeadersFromUpload(Argument::any())->willReturn(new NoContentResponse());
        $gone = $this->prophesize(Client410GoneExceptionFactory::class);
        $gone->createFromTemplate()->will(fn () => new Client410GoneException('gone'));

        $this->uploadConsistencyService = $this->prophesize(UploadConsistencyService::class);

        return new HeadUploadController(
            $authProvider->reveal(),
            $accessChecker->reveal(),
            $elementManager->reveal(),
            $noContentResponseFactory->reveal(),
            $uploadFactory->reveal(),
            $this->prophesize(UploadCancellationService::class)->reveal(),
            $this->uploadConsistencyService->reveal(),
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $gone->reveal(),
        );
    }

    public function testHeadOfExpiredUploadThrowsGone(): void
    {
        $controller = $this->buildController('-1 hour');
        $this->uploadConsistencyService->assertConsistent(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client410GoneException::class);
        $controller->headUpload(self::UPLOAD_ID);
    }

    public function testHeadOfActiveUploadChecksConsistencyAndReturnsNoContent(): void
    {
        $controller = $this->buildController('+1 hour');
        $this->uploadConsistencyService->assertConsistent(Argument::cetera())->shouldBeCalledOnce();

        $this->assertInstanceOf(NoContentResponse::class, $controller->headUpload(self::UPLOAD_ID));
    }
}
