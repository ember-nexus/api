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
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\SecurityUtilService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(SecurityUtilService::class)]
class SecurityUtilServiceTest extends TestCase
{
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
        $server500LogicExceptionFactory = new Server500LogicExceptionFactory(
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
        $bag->method('get')->willReturn('b8535d33-235f-4f71-811a-02145bf641c6');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('3207d629-7199-4c2d-9b2a-b0fba10fe309'));
        try {
            $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client400MissingPropertyException::class, $e);
            /**
             * @var $e Client400MissingPropertyException
             */
            $this->assertSame("Endpoint requires that the request contains property '_passwordHash' to be set to string.", $e->getDetail());
        }
    }

    public function testValidatePasswordMatchesWithBadPassword(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('b8535d33-235f-4f71-811a-02145bf641c6');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(false);

        $securityUtilService = $this->getSecurityUtilService(
            userPasswordHasher: $userPasswordHasher,
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('2c6c7f9a-10c9-434c-b770-9733b749b82c'));
        $userNode->addProperty('_passwordHash', 'someHash');
        try {
            $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        } catch (Exception $e) {
            $this->assertInstanceOf(Client401UnauthorizedException::class, $e);
            /**
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testValidatePasswordMatchesWithCorrectPassword(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('b8535d33-235f-4f71-811a-02145bf641c6');

        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('verifyPassword')->willReturn(true);

        $securityUtilService = $this->getSecurityUtilService(
            userPasswordHasher: $userPasswordHasher,
            bag: $bag
        );

        $userNode = new NodeElement();
        $userNode->setId(Uuid::fromString('4b08d9bd-4dfa-42f9-b433-0902d71f421e'));
        $userNode->addProperty('_passwordHash', 'someHash');
        $securityUtilService->validatePasswordMatches($userNode, 'somePassword');
        $this->expectNotToPerformAssertions();
    }

    public function testValidateUserIsNotAnonymousUserWithBadConfig(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn(null);

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('b8535d33-235f-4f71-811a-02145bf641c6');
        try {
            $securityUtilService->validateUserIsNotAnonymousUser($userId);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var $e Server500LogicErrorException
             */
            $this->assertSame('Environment variable "ANONYMOUS_USER_UUID" must be set to a valid UUID.', $e->getDetail());
        }
    }

    public function testValidateUserIsNotAnonymousUserWithAnonymousUser(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('b8535d33-235f-4f71-811a-02145bf641c6');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('b8535d33-235f-4f71-811a-02145bf641c6');
        try {
            $securityUtilService->validateUserIsNotAnonymousUser($userId);
        } catch (Exception $e) {
            $this->assertInstanceOf(Client403ForbiddenException::class, $e);
            /**
             * @var $e Client403ForbiddenException
             */
            $this->assertSame('Requested endpoint, element or action is forbidden.', $e->getDetail());
        }
    }

    public function testValidateUserIsNotAnonymousUserWithNormalUser(): void
    {
        $bag = $this->createMock(ParameterBagInterface::class);
        $bag->method('get')->willReturn('b8535d33-235f-4f71-811a-02145bf641c6');

        $securityUtilService = $this->getSecurityUtilService(
            bag: $bag
        );

        $userId = Uuid::fromString('a530420d-d426-4028-9377-9f8486ae7a4a');
        $securityUtilService->validateUserIsNotAnonymousUser($userId);
        $this->expectNotToPerformAssertions();
    }

    public function testChangeUserPassword(): void
    {
        $userPasswordHasher = $this->createMock(UserPasswordHasher::class);
        $userPasswordHasher->method('hashPassword')->willReturn('someHash');

        $elementManager = $this->createMock(ElementManager::class);
        $elementManager->method('merge')
            ->with(
                $this->callback(function (NodeElementInterface $userNode) {
                    return $userNode->hasProperty('_passwordHash')
                        && 'someHash' === $userNode->getProperty('_passwordHash');
                })
            );

        $userNode = new NodeElement();

        $securityUtilService = $this->getSecurityUtilService(
            elementManager: $elementManager,
            userPasswordHasher: $userPasswordHasher
        );

        $securityUtilService->changeUserPassword($userNode, '1234');

        $this->expectNotToPerformAssertions();
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
        $cypherEntityManager = $this->createMock(EntityManager::class);
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
             * @var $e Client401UnauthorizedException
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
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                    new CypherMap([
                        'id' => '4eb44f78-f793-4610-b857-78ce12462fff',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
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
             * @var $e Client401UnauthorizedException
             */
            $this->assertSame("Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user's unique identifier, or the user's status (e.g., missing, blocked, or deleted).", $e->getDetail());
        }
    }

    public function testFindUserByUniqueUserIdentifierWithMissingNode(): void
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

        $securityUtilService = $this->getSecurityUtilService(
            emberNexusConfiguration: $emberNexusConfiguration,
            cypherEntityManager: $cypherEntityManager,
            elementManager: $elementManager
        );

        try {
            $securityUtilService->findUserByUniqueUserIdentifier('test@localhost.dev');
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
                        'id' => 'd8defdd8-8e79-40af-84dd-169587bf2bcc',
                    ]),
                ]
            ));
        $cypherEntityManager = $this->createMock(EntityManager::class);
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
}
