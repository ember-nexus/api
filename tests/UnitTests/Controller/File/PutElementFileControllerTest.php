<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\File;

use App\Controller\File\PutElementFileController;
use App\Exception\Client404NotFoundException;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\FileCreationLockService;
use App\Service\UploadCreationService;
use App\Type\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(PutElementFileController::class)]
class PutElementFileControllerTest extends TestCase
{
    use ProphecyTrait;

    private const string ID = '6d3d983e-8cc0-43b7-88f9-5595d5ca0ad1';

    private ObjectProphecy $accessChecker;
    private ObjectProphecy $uploadCreationService;
    private ObjectProphecy $fileCreationLockService;

    private function buildController(): PutElementFileController
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn(Uuid::uuid4());
        $this->accessChecker = $this->prophesize(AccessChecker::class);
        $this->accessChecker->hasAccessToElement(Argument::cetera())->willReturn(true);
        $this->uploadCreationService = $this->prophesize(UploadCreationService::class);
        $this->fileCreationLockService = $this->prophesize(FileCreationLockService::class);
        $this->fileCreationLockService->isLocked(Argument::any())->willReturn(false);

        $client404 = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $client404->createFromTemplate()->willReturn(new Client404NotFoundException('type'));
        $client409 = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409->createFromDetail(Argument::cetera())->willReturn(new Client409ConflictException('type'));

        return new PutElementFileController(
            $authProvider->reveal(),
            $this->accessChecker->reveal(),
            $this->uploadCreationService->reveal(),
            $client404->reveal(),
            $client409->reveal(),
            $this->fileCreationLockService->reveal(),
        );
    }

    public function testReplacesFile(): void
    {
        $controller = $this->buildController();
        $request = new Request();
        $response = new NoContentResponse();
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::any(), $request)->shouldBeCalledOnce()->willReturn($response);
        $this->fileCreationLockService->acquire(Argument::any())->shouldNotBeCalled();

        $this->assertSame($response, $controller->putElementFile(self::ID, $request));
    }

    public function testWithoutUpdateAccessAnswers404(): void
    {
        $controller = $this->buildController();
        $this->accessChecker->hasAccessToElement(Argument::cetera())->willReturn(false);

        $this->expectException(Client404NotFoundException::class);
        $controller->putElementFile(self::ID, new Request());
    }

    public function testWhileFileIsBeingCreatedAnswers409(): void
    {
        $controller = $this->buildController();
        $this->fileCreationLockService->isLocked(Argument::any())->willReturn(true);
        $this->uploadCreationService->handleUploadCreationFromRequest(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Client409ConflictException::class);
        $controller->putElementFile(self::ID, new Request());
    }
}
