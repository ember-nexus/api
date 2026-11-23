<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Exception\Client401UnauthorizedException;
use App\Exception\Client403ForbiddenException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\UserPasswordHasher;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
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
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Validates the password and transparently upgrades outdated password hashes (e.g. Argon2i) to the current
     * algorithm. In that case the element manager's flush method is called.
     *
     * @throws Server500LogicErrorException
     */
    public function validatePasswordMatches(NodeElementInterface $userNode, string $currentPassword): void
    {
        if (!$userNode->hasProperty('_passwordHash')) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('_passwordHash', 'string');
        }
        $hashedPassword = $userNode->getStringProperty('_passwordHash');
        if (true !== $this->userPasswordHasher->verifyPassword($currentPassword, $hashedPassword)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        if ($this->userPasswordHasher->needsRehash($hashedPassword)) {
            $this->changeUserPassword($userNode, $currentPassword);
        }
    }

    /**
     * **Note:** This function calls the element manager's flush method.
     */
    public function changeUserPassword(NodeElementInterface $userNode, string $newPassword): void
    {
        $newPasswordHash = $this->userPasswordHasher->hashPassword($newPassword);
        $userNode->addProperty('_passwordHash', $newPasswordHash);
        $this->elementManager->merge($userNode);
        $this->elementManager->flush();
    }

    /**
     * @throws Server500LogicErrorException
     * @throws Client403ForbiddenException
     */
    public function validateUserIsNotAnonymousUser(Uuid $userId): void
    {
        $anonymousUserId = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserId)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Environment variable "ANONYMOUS_USER_UUID" must be set to a valid UUID.');
        }
        $anonymousUserId = Uuid::fromString($anonymousUserId);
        if ($userId->equals($anonymousUserId)) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }
    }

    /**
     * @param string|null $passwordToVerify the password which is checked next; if the user does not exist, a dummy verification
     *                                      with the same cost is done, so the response time does not reveal registered users
     *
     * @throws Client401UnauthorizedException
     * @throws Server500LogicErrorException
     */
    public function findUserByUniqueUserIdentifier(string $uniqueUserIdentifier, ?string $passwordToVerify = null): NodeElementInterface
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
            if (null !== $passwordToVerify) {
                // same cost as the verification of an existing account, see UserPasswordHasher::verifyAgainstDummyHash()
                $this->userPasswordHasher->verifyAgainstDummyHash($passwordToVerify);
            }
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $rawUserId = $res->first()->get('id');
        if (!is_string($rawUserId)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property id as string, not %s.', get_debug_type($rawUserId)));
        }
        $userId = Uuid::fromString($rawUserId);
        $element = $this->elementManager->getElement($userId);
        if (null === $element) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        if ($element instanceof RelationElementInterface) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Impossible situation, found relation by looking for user node.');
        }

        return $element;
    }
}
