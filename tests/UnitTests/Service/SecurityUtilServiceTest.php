<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Exception\Client400MissingPropertyException;
use App\Exception\Client401UnauthorizedException;
use App\Exception\Client403ForbiddenException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\SecurityUtilService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(SecurityUtilService::class)]
#[AllowMockObjectsWithoutExpectations]
class SecurityUtilServiceTest extends TestCase
{
    use ProphecyTrait;

    private function getSecurityUtilService(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?CypherEntityManager $cypherEntityManager = null,
        ?ElementManager $elementManager = null,
        ?UserPasswordHasher $userPasswordHasher = null,
        ?ParameterBagInterface $bag = null,
    ): SecurityUtilService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $server500Bag = $this->createMock(ParameterBagInterface::class);
        $server500Bag->method('get')->willReturn('dev');
        $server500LogicExceptionFactory = new Server500LogicErrorExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $this->createMock(LoggerInterface::class)
        );
        $client400MissingPropertyExceptionFactory = new Client400MissingPropertyExceptionFactory($urlGenerator);
        $client401UnauthorizedExceptionFactory = new Client401UnauthorizedExceptionFactory($urlGenerator);
        $client403ForbiddenExceptionFactory = new Client403ForbiddenExceptionFactory($urlGenerator);

        return new SecurityUtilService(
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $cypherEntityManager ?? $this->createMock(CypherEntityManager::class),
            $elementManager ?? $this->createMock(ElementManager::class),
            $userPasswordHasher ?? $this->createMock(UserPasswordHasher::class),
            $bag ?? $this->createMock(ParameterBagInterface::class),
            $client400MissingPropertyExceptionFactory,
            $client401UnauthorizedExceptionFactory,
            $client403ForbiddenExceptionFactory,
            $server500LogicExceptionFactory,
        );
    }

    public function testValidatePasswordMatchesWithMissingProperty(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW'));
        try {
            $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var Client400MissingPropertyException $e
             */
            $this->assertSame("Endpoint requires that the request contains property '_passwordHash' to be set to string.", $e->getDetail());
        }
    }

    public function testValidatePasswordMatchesWithBadPassword(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(false);

        $securityUtilService = $this->getSecurityUtilService(
            userPasswordHasher: $userPasswordHasher,
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('02IiNr0G8viOVKjgoVhR3hsN5WT28D4nU8TuK'));
        $userNode->addProperty('_passwordHash', 'someHash');
        try {
            $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testValidatePasswordMatchesWithCorrectPassword(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(true);

        $securityUtilService = $this->getSecurityUtilService(
            userPasswordHasher: $userPasswordHasher,
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('02IiNr0G8viOVKjvj6GyAIgDWCgVvNJG8uy6m'));
        $userNode->addProperty('_passwordHash', 'someHash');
        $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        $this->expectNotToPerformAssertions();
    }

    public function testValidatePasswordMatchesRehashesOutdatedHash(): void
    {
        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(true);
        $userPasswordHasher->method('needsRehash')->willReturn(true);
        $userPasswordHasher->expects($this->once())->method('hashPassword')->with('somePassword')->willReturn('newHash');

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->expects($this->once())->method('merge');
        $elementManager->expects($this->once())->method('flush');

        $securityUtilService = $this->getSecurityUtilService(
            elementManager: $elementManager,
            userPasswordHasher: $userPasswordHasher,
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('02IiNr0G8viOVKjvj6GyAIgDWCgVvNJG8uy6m'));
        $userNode->addProperty('_passwordHash', 'oldHash');
        $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        $this->assertSame('newHash', $userNode->getStringProperty('_passwordHash'));
    }

    public function testValidateUserIsNotAnonymousUserWithBadConfig(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn(null);

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');
        try {
            $securityUtilService->validateUserIsNotAnonymousUser($userId);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Environment variable "ANONYMOUS_USER_UUID" must be set to a valid UUID.', $e->getDetail());
        }
    }

    public function testValidateUserIsNotAnonymousUserWithAnonymousUser(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');
        try {
            $securityUtilService->validateUserIsNotAnonymousUser($userId);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client403ForbiddenException::class, $e);
            /**
             * @var Client403ForbiddenException $e
             */
            $this->assertSame('Requested endpoint, element or action is forbidden.', $e->getDetail());
        }
    }

    public function testValidateUserIsNotAnonymousUserWithNormalUser(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('02IiNr0G8viOVKkmxNLT7iu90eJrTtMp7MxDT');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('02IiNr0G8viOVKkddwH2CCUubKbySd0PQFxbf');
        $securityUtilService->validateUserIsNotAnonymousUser($userId);
        $this->expectNotToPerformAssertions();
    }

    public function testChangeUserPassword(): void
    {
        $userNode = $this->prophesize(NodeElementInterface::class);
        $userNode->addProperty(Argument::is('_passwordHash'), Argument::is('newPasswordHash'))->shouldBeCalledOnce()->willReturn($this->prophesize(NodeElementInterface::class)->reveal());
        $userNode = $userNode->reveal();

        $userPasswordHasher = $this->prophesize(UserPasswordHasher::class);
        $userPasswordHasher->hashPassword(Argument::is('newPassword'))->willReturn('newPasswordHash');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->merge(Argument::is($userNode))->shouldBeCalledOnce()->willReturn($this->prophesize(ElementManager::class)->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($this->prophesize(ElementManager::class)->reveal());

        $securityUtilService = $this->getSecurityUtilService(
            elementManager: $elementManager->reveal(),
            userPasswordHasher: $userPasswordHasher->reveal(),
        );

        $securityUtilService->changeUserPassword($userNode, 'newPassword');
    }

    public function testFindUserByUniqueUserIdentifierWithNoResults(): void
    {
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
        $cypherEntityManager = $this->createMock(CypherEntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithTooManyResults(): void
    {
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
                        'id' => '02IiNr0G8viOVKl2mgfXuczNayTF7iW6bHLJu',
                    ]),
                    new CypherMap([
                        'id' => '02IiNr0G8viOVKjxWOkGILVaIxfSIpj7r51FV',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(CypherEntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithMissingNode(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');

        $expectedCypherQuery = 'MATCH (u:User {email: $uniqueUserIdentifier}) RETURN u.id AS id';

        $null = null;
        $cypherClient = $this->prophesize(ClientInterface::class);
        $cypherClient->runStatement(
            Argument::that(function (Statement $statement) use ($expectedCypherQuery) {
                return $statement->getText() === $expectedCypherQuery;
            })
        )
            ->shouldBeCalledOnce()
            ->willReturn(new SummarizedResult(
                $null,
                [
                    new CypherMap([
                        'id' => '02IiNr0G8viOVKl2mgfXuczNayTF7iW6bHLJu',
                    ]),
                ]
            ));

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($cypherClient->reveal());

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(
            Argument::that(function (Uuid $elementId) {
                return '02IiNr0G8viOVKl2mgfXuczNayTF7iW6bHLJu' === $elementId->toString();
            })
        )
            ->shouldBeCalledOnce()
            ->willReturn(null);

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            cypherEntityManager: $cypherEntityManager->reveal(),
            elementManager: $elementManager->reveal(),
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
            $this->fail('Expected Client401UnauthorizedException to be thrown.');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var Client401UnauthorizedException $e
             */
            $this->assertSame(
                "Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).",
                $e->getDetail()
            );
        }
    }

    public function testFindUserByUniqueUserIdentifierWithRelation(): void
    {
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
                        'id' => '02IiNr0G8viOVKl2mgfXuczNayTF7iW6bHLJu',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(CypherEntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->willReturn(new RelationElement());

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
        }
    }

    public function testFindUserByUniqueUserIdentifierWithNode(): void
    {
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
                        'id' => '02IiNr0G8viOVKl2mgfXuczNayTF7iW6bHLJu',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(CypherEntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('getElement')
            ->willReturn(new NodeElement());

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager,
        );

        $userNode = $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
        $this->assertInstanceOf(NodeElement::class, $userNode);
    }

    public function testFindUserByUniqueUserIdentifierWithBadNode(): void
    {
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
                        'id' => 5345354,
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(CypherEntityManager::class);
        $cypherEntityManager->method('getClient')->willReturn($cypherClient);

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame('Expected cypher response to return property id as string, not int.', $e->getDetail());
        }
    }
}
