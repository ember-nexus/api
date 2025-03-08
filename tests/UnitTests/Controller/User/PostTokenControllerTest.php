<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Controller\User;

use App\Controller\User\PostTokenController;
use App\Exception\Client400BadContentException;
use App\Exception\Client401UnauthorizedException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
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
#[CoversClass(PostTokenController::class)]
class PostTokenControllerTest extends TestCase
{
    use ProphecyTrait;

    private function getPostTokenController(
        ?TokenGenerator $tokenGenerator = null,
        ?RequestUtilService $requestUtilService = null,
        ?SecurityUtilService $securityUtilService = null,
    ): PostTokenController {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');

        return new PostTokenController(
            $tokenGenerator ?? $this->createMock(TokenGenerator::class),
            new Client400BadContentExceptionFactory($urlGenerator),
            new Client401UnauthorizedExceptionFactory($urlGenerator),
            $requestUtilService ?? $this->createMock(RequestUtilService::class),
            $securityUtilService ?? $this->createMock(SecurityUtilService::class)
        );
    }

    public function testPostTokenWithWorkingUser(): void
    {
        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService->method('getUniqueUserIdentifierFromBodyAndData')->willReturn('test@example.com');
        $requestUtilService->method('getStringFromBody')->willReturn('1234');
        $requestUtilService->method('getDataFromBody')->willReturn([]);

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('018ed248-d1de-4e20-9e61-30d1a479215c'));

        $securityUtilService = $this->createMock(SecurityUtilService::class);
        $securityUtilService->method('findUserByUniqueUserIdentifier')->willReturn($userNode);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->method('createNewToken')->willReturn('someToken');

        $postTokenController = $this->getPostTokenController(
            tokenGenerator: $tokenGenerator,
            requestUtilService: $requestUtilService,
            securityUtilService: $securityUtilService
        );

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"type": "Token", "password": "1234", "uniqueUserIdentifier": "test@example.com"}');

        $response = $postTokenController->postToken($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $responseBody = json_decode($response->getContent(), true);
        $this->assertSame('_TokenResponse', $responseBody['type']);
        $this->assertSame('someToken', $responseBody['token']);
    }

    public function testPostTokenWithBadUser(): void
    {
        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService->method('getUniqueUserIdentifierFromBodyAndData')->willReturn('test@example.com');

        $securityUtilService = $this->createMock(SecurityUtilService::class);
        $securityUtilService->method('findUserByUniqueUserIdentifier')->willReturn(new NodeElement());

        $postTokenController = $this->getPostTokenController(
            requestUtilService: $requestUtilService,
            securityUtilService: $securityUtilService
        );

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"type": "Token", "password": "1234", "uniqueUserIdentifier": "test@example.com"}');

        try {
            $postTokenController->postToken($request);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testCreateTokenResponse(): void
    {
        $postTokenController = $this->getPostTokenController();
        $method = new ReflectionMethod(PostTokenController::class, 'createTokenResponse');
        $method->setAccessible(true);

        $response = $method->invokeArgs($postTokenController, ['1234']);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $body = \Safe\json_decode($response->getContent(), true);
        $this->assertSame('_TokenResponse', $body['type']);
        $this->assertSame('1234', $body['token']);
    }

    public function testGetLifetimeInSecondsFromBody(): void
    {
        $postTokenController = $this->getPostTokenController();
        $method = new ReflectionMethod(PostTokenController::class, 'getLifetimeInSecondsFromBody');
        $method->setAccessible(true);

        $body = [];
        $result = $method->invokeArgs($postTokenController, [$body]);
        $this->assertNull($result);

        $body = [
            'lifetimeInSeconds' => '1234',
        ];
        try {
            $method->invokeArgs($postTokenController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'lifetimeInSeconds' to be int, got 'string'.", $e->getDetail());
        }

        $body = [
            'lifetimeInSeconds' => [
                'hello' => 'world',
            ],
        ];
        try {
            $method->invokeArgs($postTokenController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var Client400BadContentException $e
             */
            $this->assertSame("Endpoint expects property 'lifetimeInSeconds' to be int, got 'array'.", $e->getDetail());
        }

        $body = [
            'lifetimeInSeconds' => 1234,
        ];
        $result = $method->invokeArgs($postTokenController, [$body]);
        $this->assertSame(1234, $result);
    }
}
