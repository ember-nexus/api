<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Exception\Client401UnauthorizedException;
use App\Exception\Client403ForbiddenException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\UserPasswordHasher;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class SecurityUtilService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private UserPasswordHasher $userPasswordHasher,
        private ParameterBagInterface $bag,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    /**
     * @throws Server500LogicErrorException
     */
    public function validatePasswordMatches(NodeElementInterface $userNode, string $currentPassword): void
    {
        if (!$userNode->hasProperty('_passwordHash')) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Unable to find property "_passwordHash" on user with id "%s".', $userNode->getIdentifier()?->toString() ?? 'no identifier'));
        }
        $hashedPassword = $userNode->getProperty('_passwordHash');
        if (true !== $this->userPasswordHasher->verifyPassword($currentPassword, $hashedPassword)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Unable to verify password on user with id "%s".', $userNode->getIdentifier()?->toString() ?? 'no identifier'));
        }
    }

    /**
     * @throws Server500LogicErrorException
     * @throws Client403ForbiddenException
     */
    public function validateUserIsNotAnonymousUser(UuidInterface $userId): void
    {
        $anonymousUserUuid = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserUuid)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Environment variable "ANONYMOUS_USER_UUID" must be set to a valid UUID.');
        }
        $anonymousUserUuid = UuidV4::fromString($anonymousUserUuid);
        if ($userId->equals($anonymousUserUuid)) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }
    }

    /**
     * @throws Client401UnauthorizedException
     * @throws Server500LogicErrorException
     */
    public function findUserByUniqueUserIdentifier(string $uniqueUserIdentifier): NodeElementInterface
    {
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            sprintf(
                'MATCH (u:User {%s: $uniqueUserIdentifier}) RETURN u.id AS id',
                $uniqueIdentifier,
            ),
            [
                'uniqueUserIdentifier' => $uniqueUserIdentifier,
            ]
        ));
        if (1 !== count($res)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $userUuid = Uuid::fromString($res->first()->get('id'));
        $element = $this->elementManager->getElement($userUuid);
        if (null === $element) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        if ($element instanceof RelationElementInterface) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Impossible situation, found relation by looking for user node.');
        }

        return $element;
    }
}
