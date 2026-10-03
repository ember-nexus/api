<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\User;

use App\Contract\NodeElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Controller\User\DeleteTokenController;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Security\AuthProvider;
use App\Service\ElementFileDeletionService;
use App\Service\ElementManager;
use App\Service\UploadService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(DeleteTokenController::class)]
class DeleteTokenControllerTest extends TestCase
{
    use ProphecyTrait;

    public function testDeletingTokenAlsoDeletesFilesAndTargetingUploadsInOrder(): void
    {
        $tokenId = Uuid::uuid4();
        $tokenElement = $this->prophesize(NodeElementInterface::class)->reveal();
        $fileOperation = $this->prophesize(FileOperationInterface::class)->reveal();

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn(false);
        $authProvider->getHashedToken()->willReturn('hash');
        $authProvider->getTokenId()->willReturn($tokenId);

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail($tokenId)->willReturn($tokenElement);

        $fileDeletionService = $this->prophesize(ElementFileDeletionService::class);
        $fileDeletionService->getFileOperationsForDeletionOfElement($tokenElement)->willReturn([$fileOperation]);

        $uploadService = $this->prophesize(UploadService::class);

        $uploadService->deleteUploadsTargeting($tokenId)->shouldBeCalledOnce();
        $elementManager->delete($tokenElement)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledTimes(2)->willReturn($elementManager->reveal());
        // files are deleted after the token is deleted and flushed
        $fileDeletionService->deleteFiles([$fileOperation])->shouldBeCalledOnce();

        $controller = new DeleteTokenController(
            $elementManager->reveal(),
            $authProvider->reveal(),
            $uploadService->reveal(),
            $fileDeletionService->reveal(),
            $this->prophesize(Client401UnauthorizedExceptionFactory::class)->reveal(),
        );

        $response = $controller->deleteToken();

        $this->assertSame(204, $response->getStatusCode());
    }
}
