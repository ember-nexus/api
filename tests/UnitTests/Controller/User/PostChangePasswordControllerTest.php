<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\User;

use App\Controller\User\PostChangePasswordController;
use App\Exception\Client400BadContentException;
use App\Exception\Client401UnauthorizedException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Response\NoContentResponse;
use App\Service\RequestUtilService;
use App\Service\SecurityUtilService;
use App\Type\NodeElement;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(PostChangePasswordController::class)]
class PostChangePasswordControllerTest extends TestCase
{
    use ProphecyTrait;

    private function getPostChangePasswordController(
        ?RequestUtilService $requestUtilService = null,
        ?SecurityUtilService $securityUtilService = null,
    ): PostChangePasswordController {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');

        return new PostChangePasswordController(
            $requestUtilService ?? $this->createMock(RequestUtilService::class),
            $securityUtilService ?? $this->createMock(SecurityUtilService::class),
            new Client400BadContentExceptionFactory($urlGenerator),
            new Client401UnauthorizedExceptionFactory($urlGenerator),
        );
    }

    public function testPostChangePassword(): void
    {
        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService
            ->method('getStringFromBody')
            ->willReturnOnConsecutiveCalls(
                '4321',
                '1234'
            );
        $requestUtilService->method('getUniqueUserIdentifierFromBodyAndData')->willReturn('test@example.com');

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('6d3d983e-8cc0-43b7-88f9-5595d5ca0ad1'));

        $securityUtilService = $this->createMock(SecurityUtilService::class);
        $securityUtilService->method('findUserByUniqueUserIdentifier')->willReturn($userNode);

        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn('{"type": "ActionChangePassword", "newPassword": "4321", "currentPassword": "1234", "uniqueUserIdentifier": "test@example.com"}');

        $postChangePasswordController = $this->getPostChangePasswordController(
            requestUtilService: $requestUtilService,
            securityUtilService: $securityUtilService
        );
        $response = $postChangePasswordController->postChangePassword($request);
        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testPostChangePasswordWithBadUser(): void
    {
        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService
            ->method('getStringFromBody')
            ->willReturnOnConsecutiveCalls(
                '4321',
                '1234'
            );
        $requestUtilService->method('getUniqueUserIdentifierFromBodyAndData')->willReturn('test@example.com');

        $userNode = new NodeElement();

        $securityUtilService = $this->createMock(SecurityUtilService::class);
        $securityUtilService->method('findUserByUniqueUserIdentifier')->willReturn($userNode);

        $request = $this->createMock(Request::class);
        $request->method('getContent')
            ->willReturn('{"type": "ActionChangePassword", "newPassword": "4321", "currentPassword": "1234", "uniqueUserIdentifier": "test@example.com"}');

        $postChangePasswordController = $this->getPostChangePasswordController(
            requestUtilService: $requestUtilService,
            securityUtilService: $securityUtilService
        );
        try {
            $postChangePasswordController->postChangePassword($request);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testValidateNewPasswordIsDifferentFromCurrentPassword(): void
    {
        $postChangePasswordController = $this->getPostChangePasswordController();

        $method = new ReflectionMethod(PostChangePasswordController::class, 'validateNewPasswordIsDifferentFromCurrentPassword');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postChangePasswordController, ['1234', '1234']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'newPassword' to be password which is not identical to the old password, got '<redacted>'.", $e->getDetail());
        }

        $method->invokeArgs($postChangePasswordController, ['1234', '4321']);
    }
}
