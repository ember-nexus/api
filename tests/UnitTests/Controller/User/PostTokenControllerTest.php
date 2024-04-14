<?php

namespace App\tests\UnitTests\Controller\User;

use App\Controller\User\PostTokenController;
use App\Exception\Client400BadContentException;
use App\Exception\Client401UnauthorizedException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\UuidInterface;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PostTokenControllerTest extends TestCase
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

    public function testCreateTokenResponse(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

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

    public function testVerifyPasswordMatchesWithWrongData(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService->method('getPasswordFromBody')->willReturn('1234');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(false);

        $postTokenController = $this->getPostTokenController(
            userPasswordHasher: $userPasswordHasher,
            requestUtilService: $requestUtilService
        );
        $method = new ReflectionMethod(PostTokenController::class, 'verifyPasswordMatches');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postTokenController, [[], new NodeElement()]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }

        $node = new NodeElement();
        $node->addProperty('_passwordHash', 'hashed-password');
        try {
            $method->invokeArgs($postTokenController, [[], $node]);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testVerifyPasswordMatchesWithCorrectData(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $requestUtilService = $this->createMock(RequestUtilService::class);
        $requestUtilService->method('getPasswordFromBody')->willReturn('1234');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(true);

        $postTokenController = $this->getPostTokenController(
            userPasswordHasher: $userPasswordHasher,
            requestUtilService: $requestUtilService
        );
        $method = new ReflectionMethod(PostTokenController::class, 'verifyPasswordMatches');
        $method->setAccessible(true);

        $node = new NodeElement();
        $node->addProperty('_passwordHash', 'hashed-password');
        $method->invokeArgs($postTokenController, [[], $node]);
        $this->expectNotToPerformAssertions();
    }

    public function testGetLifetimeInSecondsFromBody(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

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
             * @var $e Client400BadContentException
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
             * @var $e Client400BadContentException
             */
            $this->assertSame("Endpoint expects property 'lifetimeInSeconds' to be int, got 'array'.", $e->getDetail());
        }

        $body = [
            'lifetimeInSeconds' => 1234,
        ];
        $result = $method->invokeArgs($postTokenController, [$body]);
        $this->assertSame(1234, $result);
    }

    public function testFindUserByUniqueUserIdentifierWithNoResults(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN u.id AS id';

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
                []
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $postTokenController = $this->getPostTokenController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );
        $method = new ReflectionMethod(PostTokenController::class, 'findUserByUniqueUserIdentifier');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postTokenController, ['test@localhost.dev']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithTooManyResults(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN u.id AS id';

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
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                    new CypherMap([
                        'id' => '4eb44f78-f793-4610-b857-78ce12462fff',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $postTokenController = $this->getPostTokenController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );
        $method = new ReflectionMethod(PostTokenController::class, 'findUserByUniqueUserIdentifier');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postTokenController, ['test@localhost.dev']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithMissingNode(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN u.id AS id';

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
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->with(
                $this->callback(function (UuidInterface $elementId) {
                    return 'd8defdd8-8e79-40af-84dd-169587bf2bcc' === $elementId->toString();
                })
            )
            ->willReturn(null);

        $postTokenController = $this->getPostTokenController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager
        );
        $method = new ReflectionMethod(PostTokenController::class, 'findUserByUniqueUserIdentifier');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postTokenController, ['test@localhost.dev']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithRelation(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN u.id AS id';

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
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->willReturn(new RelationElement());

        $server500LogicExceptionFactory = $this->createMock(Server500LogicExceptionFactory::class);
        $server500LogicExceptionFactory
            ->method('createFromTemplate')
            ->with(
                $this->callback(function (string $message) {
                    return 'Impossible situation, found relation by looking for user node.' === $message;
                })
            )
            ->willReturn(
                $this->createMock(Server500LogicErrorException::class)
            );

        $postTokenController = $this->getPostTokenController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager,
            server500LogicExceptionFactory: $server500LogicExceptionFactory
        );
        $method = new ReflectionMethod(PostTokenController::class, 'findUserByUniqueUserIdentifier');
        $method->setAccessible(true);

        try {
            $method->invokeArgs($postTokenController, ['test@localhost.dev']);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
        }
    }

    public function testFindUserByUniqueUserIdentifierWithNode(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }

        $emberNexusConfiguration = $this->createMock(EmberNexusConfiguration::class);
        $emberNexusConfiguration->method('getRegisterUniqueIdentifier')->willReturn('email');

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

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->willReturn(new NodeElement());

        $postTokenController = $this->getPostTokenController(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager,
        );
        $method = new ReflectionMethod(PostTokenController::class, 'findUserByUniqueUserIdentifier');
        $method->setAccessible(true);

        $userNode = $method->invokeArgs($postTokenController, ['test@localhost.dev']);
        $this->assertInstanceOf(NodeElement::class, $userNode);
    }
}
