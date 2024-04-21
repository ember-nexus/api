<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Controller\User;

use App\Controller\User\PostRegisterController;
use App\Exception\Client400ReservedIdentifierException;
use App\Exception\Client403ForbiddenException;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use App\Type\NodeElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Nonstandard\Uuid;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;

class PostRegisterControllerTest extends TestCase
{
    use ProphecyTrait;

    private function getPostRegisterController(
        ?ElementManager $elementManager = null,
        ?EntityManager $cypherEntityManager = null,
        ?UrlGeneratorInterface $router = null,
        ?UserPasswordHasher $userPasswordHasher = null,
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory = null,
        ?Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory = null,
        ?RequestUtilService $requestUtilService = null
    ): PostRegisterController {
        return new PostRegisterController(
            $elementManager ?? $this->createMock(ElementManager::class),
            $cypherEntityManager ?? $this->createMock(EntityManager::class),
            $router ?? $this->createMock(UrlGeneratorInterface::class),
            $userPasswordHasher ?? $this->createMock(UserPasswordHasher::class),
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $client400ReservedIdentifierExceptionFactory ?? $this->createMock(Client400ReservedIdentifierExceptionFactory::class),
            $client403ForbiddenExceptionFactory ?? $this->createMock(Client403ForbiddenExceptionFactory::class),
            $requestUtilService ?? $this->createMock(RequestUtilService::class)
        );
    }

    public function testPostRegisterWithEnabledRegister(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $emberNexusConfiguration->method('isRegisterEnabled')->willReturn(true);

        $null = null;
        $cypherClient = $this->createMock(ClientInterface::class);
        $cypherClient->expects($this->once())
            ->method('runStatement')
            ->willReturn(new SummarizedResult(
                $null,
                [
                    new CypherMap([
                        'count' => 0,
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $postRegisterController = $this->getPostRegisterController(
            emberNexusConfiguration: $emberNexusConfiguration,
            userPasswordHasher: new UserPasswordHasher(),
            router: $urlGenerator,
            cypherEntityManager: $cypherEntityManager
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'postRegister');
        $method->setAccessible(true);

        $request = $this->createMock(Request::class);
        $request->method('getContent')->willReturn('{"type": "User", "password": "1234", "uniqueUserIdentifier": "test@example.com"}');

        $response = $postRegisterController->postRegister($request);
        $this->assertInstanceOf(CreatedResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('url', $response->headers->get('Location'));
    }

    public function testPostRegisterWithDisabledRegister(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('isRegisterEnabled')->willReturn(false);
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client403ForbiddenExceptionFactory = new Client403ForbiddenExceptionFactory($urlGenerator);

        $postRegisterController = $this->getPostRegisterController(
            emberNexusConfiguration: $emberNexusConfiguration,
            client403ForbiddenExceptionFactory: $client403ForbiddenExceptionFactory
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'postRegister');
        $method->setAccessible(true);

        $request = $this->createMock(Request::class);

        try {
            $postRegisterController->postRegister($request);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client403ForbiddenException::class, $e);
            /**
             * @var $e Client403ForbiddenException
             */
            $this->assertSame('Requested endpoint, element or action is forbidden.', $e->getDetail());
        }
    }

    public function testCheckForDuplicateUniqueUserIdentifierWithDuplicate(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN count(u) AS count';

        $null = null;
        $cypherClient = $this->createMock(ClientInterface::class);
        $cypherClient->expects($this->once())
            ->method('runStatement')
            ->with(
                $this->callback(function (Statement $statement) use ($expectedCypherQuery) {
                    return $statement->getText() === $expectedCypherQuery;
                })
            )
            ->willReturn(new SummarizedResult(
                $null,
                [
                    new CypherMap([
                        'count' => 1,
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);
        $postRegisterController = $this->getPostRegisterController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'checkForDuplicateUniqueUserIdentifier');
        $method->setAccessible(true);

        $userId = Uuid::uuid4();
        try {
            $method->invokeArgs($postRegisterController, [$userId]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400ReservedIdentifierException::class, $e);
        }
    }

    public function testCheckForDuplicateUniqueUserIdentifierWithNoDuplicate(): void
    {
        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN count(u) AS count';

        $null = null;
        $cypherClient = $this->createMock(ClientInterface::class);
        $cypherClient->expects($this->once())
            ->method('runStatement')
            ->with(
                $this->callback(function (Statement $statement) use ($expectedCypherQuery) {
                    return $statement->getText() === $expectedCypherQuery;
                })
            )
            ->willReturn(new SummarizedResult(
                $null,
                [
                    new CypherMap([
                        'count' => 0,
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);
        $postRegisterController = $this->getPostRegisterController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'checkForDuplicateUniqueUserIdentifier');
        $method->setAccessible(true);

        $userId = Uuid::uuid4();
        $method->invokeArgs($postRegisterController, [$userId]);
    }

    public function testCreateUserNode(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $postRegisterController = $this->getPostRegisterController(
            emberNexusConfiguration: $emberNexusConfiguration,
            userPasswordHasher: new UserPasswordHasher()
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'createUserNode');
        $method->setAccessible(true);

        $userId = Uuid::uuid4();
        $data = [];
        $uniqueUserIdentifier = 'test@localhost.dev';
        $password = '1234';
        $userNode = $method->invokeArgs($postRegisterController, [$userId, $data, $uniqueUserIdentifier, $password]);
        $this->assertInstanceOf(NodeElement::class, $userNode);
        $this->assertSame($userId, $userNode->getIdentifier());
        $this->assertSame($uniqueUserIdentifier, $userNode->getProperty('email'));
        $this->assertTrue($userNode->hasProperty('_passwordHash'));

        $userId = Uuid::uuid4();
        $data = [
            'email' => 'manual-specified-email-which-should-be-overwritten@localhost.dev',
        ];
        $uniqueUserIdentifier = 'test@localhost.dev';
        $password = '1234';
        $userNode = $method->invokeArgs($postRegisterController, [$userId, $data, $uniqueUserIdentifier, $password]);
        $this->assertInstanceOf(NodeElement::class, $userNode);
        $this->assertSame($userId, $userNode->getIdentifier());
        $this->assertSame($uniqueUserIdentifier, $userNode->getProperty('email'));
        $this->assertTrue($userNode->hasProperty('_passwordHash'));
    }

    public function testCreateCreatedResponse(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $postRegisterController = $this->getPostRegisterController(
            router: $urlGenerator
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'createCreatedResponse');
        $method->setAccessible(true);

        $userId = Uuid::uuid4();
        $response = $method->invokeArgs($postRegisterController, [$userId]);
        $this->assertInstanceOf(CreatedResponse::class, $response);
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('url', $response->headers->get('Location'));
    }
}
