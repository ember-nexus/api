<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\File;

use App\Controller\File\PostElementFileController;
use App\Exception\Client404NotFoundException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileCreationLockService;
use App\Service\UploadCreationService;
use App\Service\UploadService;
use App\Type\AccessType;
use App\Type\NodeElement;
use App\Type\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(PostElementFileController::class)]
class PostElementFileControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string ID = '6d3d983e-8cc0-43b7-88f9-5595d5ca0ad1';

    private ObjectProphecy $accessChecker;
    private ObjectProphecy $uploadCreationService;
    private ObjectProphecy $elementManager;
    private ObjectProphecy $elementService;
    private ObjectProphecy $fileCreationLockService;
    private ObjectProphecy $uploadService;

    private function buildController(): PostElementFileController
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn(\Ramsey\Uuid\Uuid::uuid4());
        $this->accessChecker = $this->prophesize(AccessChecker::class);
        $this->accessChecker->hasAccessToElement(Argument::cetera())->willReturn(true);
        $this->uploadCreationService = $this->prophesize(UploadCreationService::class);
        $this->elementManager = $this->prophesize(ElementManager::class);
        $this->elementManager->getElementOrFail(Argument::any())->willReturn((new NodeElement())->setId(\Ramsey\Uuid\Uuid::fromString(self::ID)));
        $this->elementService = $this->prophesize(ElementService::class);
        $this->elementService->hasFile(Argument::any())->willReturn(false);
        $this->fileCreationLockService = $this->prophesize(FileCreationLockService::class);
        $this->fileCreationLockService->acquire(Argument::any())->willReturn('token');
        $this->uploadService = $this->prophesize(UploadService::class);
        $this->uploadService->hasUploadsTargeting(Argument::any())->willReturn(false);

        $client404 = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $client404->createFromTemplate()->willReturn(new Client404NotFoundException('type'));
        $client409 = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409->createFromDetail(Argument::cetera())->willReturn(new Client409ConflictException('type'));

        return new PostElementFileController(
            $authProvider->reveal(),
            $this->accessChecker->reveal(),
            $this->uploadCreationService->reveal(),
            $this->elementManager->reveal(),
            $this->elementService->reveal(),
            $client404->reveal(),
            $client409->reveal(),
            $this->fileCreationLockService->reveal(),
            $this->uploadService->reveal(),
        );
    }

    public function testCreatesFileAndReleasesLock(): void
    {
        $controller = $this->buildController();
        $request = new Request();
        $response = new NoContentResponse();
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::any(), $request)->shouldBeCalledOnce()->willReturn($response);
        $this->fileCreationLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->assertSame($response, $controller->postElementFile(self::ID, $request));
    }

    public function testWithoutUpdateAccessAnswers404AndDoesNotLock(): void
    {
        $controller = $this->buildController();
        $this->accessChecker->hasAccessToElement(Argument::any(), Argument::any(), AccessType::UPDATE)->willReturn(false);
        $this->fileCreationLockService->acquire(Argument::any())->shouldNotBeCalled();

        $this->expectException(Client404NotFoundException::class);
        $controller->postElementFile(self::ID, new Request());
    }

    public function testLockedElementAnswers409(): void
    {
        $controller = $this->buildController();
        $this->fileCreationLockService->acquire(Argument::any())->willReturn(null);
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::cetera())->shouldNotBeCalled();
        $this->fileCreationLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $controller->postElementFile(self::ID, new Request());
    }

    public function testElementWithFileAnswers409AndReleasesLock(): void
    {
        $controller = $this->buildController();
        $this->elementService->hasFile(Argument::any())->willReturn(true);
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::cetera())->shouldNotBeCalled();
        $this->fileCreationLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $controller->postElementFile(self::ID, new Request());
    }

    public function testElementWithUploadAnswers409AndReleasesLock(): void
    {
        $controller = $this->buildController();
        $this->uploadService->hasUploadsTargeting(Argument::any())->willReturn(true);
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::cetera())->shouldNotBeCalled();
        $this->fileCreationLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->expectException(Client409ConflictException::class);
        $controller->postElementFile(self::ID, new Request());
    }

    public function testFailingUploadReleasesLock(): void
    {
        $controller = $this->buildController();
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::cetera())->willThrow(new RuntimeException('boom'));
        $this->fileCreationLockService->release(Argument::any(), 'token')->shouldBeCalledOnce();

        $this->expectException(RuntimeException::class);
        $controller->postElementFile(self::ID, new Request());
    }
}
