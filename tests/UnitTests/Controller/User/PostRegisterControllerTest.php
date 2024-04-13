<?php

namespace App\tests\UnitTests\Controller\User;

use App\Controller\User\PostRegisterController;
use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Exception\Client400ReservedIdentifierException;
use App\Exception\Client403ForbiddenException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
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
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory = null,
        ?Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory = null,
        ?Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory = null,
    ): PostRegisterController {
        return new PostRegisterController(
            $elementManager ?? $this->createMock(ElementManager::class),
            $cypherEntityManager ?? $this->createMock(EntityManager::class),
            $router ?? $this->createMock(UrlGeneratorInterface::class),
            $userPasswordHasher ?? $this->createMock(UserPasswordHasher::class),
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $client400BadContentExceptionFactory ?? $this->createMock(Client400BadContentExceptionFactory::class),
            $client400MissingPropertyExceptionFactory ?? $this->createMock(Client400MissingPropertyExceptionFactory::class),
            $client400ReservedIdentifierExceptionFactory ?? $this->createMock(Client400ReservedIdentifierExceptionFactory::class),
            $client403ForbiddenExceptionFactory ?? $this->createMock(Client403ForbiddenExceptionFactory::class)
        );
    }

    public function testPostRegisterWithEnabledRegister(): void
    {
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

        //        $response = $method->invokeArgs($postRegisterController, [$request]);
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

    public function testGetDataFromBody(): void
    {
        $postRegisterController = $this->getPostRegisterController();
        $method = new ReflectionMethod(PostRegisterController::class, 'getDataFromBody');
        $method->setAccessible(true);

        $body = [];
        $data = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertEmpty($data);

        $body = [
            'someOtherKey' => 'test',
        ];
        $data = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertEmpty($data);

        $body = [
            'data' => [],
        ];
        $data = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertEmpty($data);

        $body = [
            'data' => [
                'key' => 'value',
            ],
        ];
        $data = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertArrayHasKey('key', $data);
    }

    public function testGetPasswordFromBody(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $postRegisterController = $this->getPostRegisterController(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory,
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'getPasswordFromBody');
        $method->setAccessible(true);

        $body = [];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'password' to be set to string.", $e->getDetail());
        }

        $body = [
            'password' => 1234,
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'password' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'password' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'password' => '1234',
        ];
        $password = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertSame('1234', $password);
    }

    public function testValidateTypeFromBody(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $postRegisterController = $this->getPostRegisterController(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory,
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'validateTypeFromBody');
        $method->setAccessible(true);

        $body = [];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'type' to be set to string.", $e->getDetail());
        }

        $body = [
            'type' => 1234,
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'type' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'type' => 'notAnUser',
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'type' to be User, got 'notAnUser'.", $e->getDetail());
        }

        $body = [
            'type' => 'User',
        ];
        $method->invokeArgs($postRegisterController, [$body]);
    }

    public function testGetUniqueUserIdentifierFromDataOld(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $postRegisterController = $this->getPostRegisterController(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory,
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory,
            emberNexusConfiguration: $emberNexusConfiguration
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'getUniqueUserIdentifierFromDataOld');
        $method->setAccessible(true);

        $data = [];
        try {
            $method->invokeArgs($postRegisterController, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'data.email' to be set to string.", $e->getDetail());
        }

        $data = [
            'email' => 1234,
        ];
        try {
            $method->invokeArgs($postRegisterController, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'data.email' to be string, got 'integer'.", $e->getDetail());
        }

        $data = [
            'email' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$data]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'data.email' to be string, got 'array'.", $e->getDetail());
        }

        $data = [
            'email' => 'test@localhost.dev',
        ];
        $uniqueUserIdentifier = $method->invokeArgs($postRegisterController, [$data]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testGetUniqueUserIdentifierFromDataNew(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $postRegisterController = $this->getPostRegisterController(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory,
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'getUniqueUserIdentifierFromBodyNew');
        $method->setAccessible(true);

        $body = [];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'uniqueUserIdentifier' to be set to string.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 1234,
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => [
                'some' => 'object',
            ],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 'test@localhost.dev',
        ];
        $uniqueUserIdentifier = $method->invokeArgs($postRegisterController, [$body]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testGetUniqueUserIdentifierFromBodyAndDataWithOldWayEnabled(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');
        $emberNexusConfiguration->method('isFeatureFlag280OldUniqueUserIdentifierDisabled')->willReturn(false);
        $postRegisterController = $this->getPostRegisterController(
            client400MissingPropertyExceptionFactory: $client400MissingPropertyExceptionFactory,
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory,
            emberNexusConfiguration: $emberNexusConfiguration
        );
        $method = new ReflectionMethod(PostRegisterController::class, 'getUniqueUserIdentifierFromBodyAndData');
        $method->setAccessible(true);

        $body = [
            'data' => [],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property 'uniqueUserIdentifier' to be set to string.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 1234,
            'data' => [],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'integer'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => [
                'some' => 'object',
            ],
            'data' => [],
        ];
        try {
            $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400BadContentException::class, $e);
            /**
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'uniqueUserIdentifier' to be string, got 'array'.", $e->getDetail());
        }

        $body = [
            'uniqueUserIdentifier' => 'test@localhost.dev',
            'data' => [],
        ];
        $uniqueUserIdentifier = $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);

        $body = [
            'uniqueUserIdentifier' => 'testNew@localhost.dev',
            'data' => [
                'email' => 'testOld@localhost.dev',
            ],
        ];
        $uniqueUserIdentifier = $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        $this->assertSame('testOld@localhost.dev', $uniqueUserIdentifier);

        $body = [
            'data' => [
                'email' => 'test@localhost.dev',
            ],
        ];
        $uniqueUserIdentifier = $method->invokeArgs($postRegisterController, [$body, $body['data']]);
        $this->assertSame('test@localhost.dev', $uniqueUserIdentifier);
    }

    public function testCheckForDuplicateUniqueUserIdentifierWithDuplicate(): void
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
