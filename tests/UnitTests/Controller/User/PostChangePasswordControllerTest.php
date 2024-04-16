<?php

namespace App\tests\UnitTests\Controller\User;

use App\Controller\User\PostTokenController;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use App\Type\NodeElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PostChangePasswordControllerTest extends TestCase
{
    use ProphecyTrait;

    private function getPostTokenController(
        ?TokenGenerator $tokenGenerator = null,
        ?Server500LogicExceptionFactory $server500LogicExceptionFactory = null,
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?CypherEntityManager $cypherEntityManager = null,
        ?ElementManager $elementManager = null,
        ?UserPasswordHasher $userPasswordHasher = null,
        ?RequestUtilService $requestUtilService = null,
    ): PostTokenController {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');

        return new PostTokenController(
            $tokenGenerator ?? $this->createMock(TokenGenerator::class),
            new Client400BadContentExceptionFactory($urlGenerator),
            new Client401UnauthorizedExceptionFactory($urlGenerator),
            $server500LogicExceptionFactory ?? $this->createMock(Server500LogicExceptionFactory::class),
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $cypherEntityManager ?? $this->createMock(CypherEntityManager::class),
            $elementManager ?? $this->createMock(ElementManager::class),
            $userPasswordHasher ?? $this->createMock(UserPasswordHasher::class),
            $requestUtilService ?? $this->createMock(RequestUtilService::class)
        );
    }

    public function testPostTokenWithWorkingUser(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService->method('getUniqueUserIdentifierFromBodyAndData')->willReturn('test@example.com');
        $requestUtilService->method('getDataFromBody')->willReturn([]);

        $tokenGenerator = $this->createMock(TokenGenerator::class);
        $tokenGenerator->method('createNewToken')->willReturn('someToken');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(true);

        $null = null;
        $cypherClient = $this->createMock(ClientInterface::class);
        $cypherClient->expects($this->once())
            ->method('runStatement')
            ->willReturn(new SummarizedResult(
                $null,
                [
                    new CypherMap([
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $node = new NodeElement();
        $node->setIdentifier(Uuid::fromString('d8defdd8-8e79-40af-84dd-169587bf2bcc'));
        $node->setLabel('User');
        $node->addProperty('email', 'test@example.com');
        $node->addProperty('_passwordHash', 'someHash');

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->willReturn($node);

        $postTokenController = $this->getPostTokenController(
            requestUtilService: $requestUtilService,
            tokenGenerator: $tokenGenerator,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager,
            userPasswordHasher: $userPasswordHasher
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
}
