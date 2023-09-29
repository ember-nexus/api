<?php

namespace App\Controller\User;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\NoContentResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PostChangePasswordController extends AbstractController
{
    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private UserPasswordHasher $userPasswordHasher,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
        private ParameterBagInterface $bag
    ) {
    }

    #[Route(
        '/change-password',
        name: 'post-change-password',
        methods: ['POST']
    )]
    public function postChangePassword(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'string');
        }
        if ('ActionChangePassword' !== $body['type']) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'ActionChangePassword', $body['type']);
        }

        if (!array_key_exists('currentPassword', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('currentPassword', 'string');
        }
        $currentPassword = $body['currentPassword'];

        if (!array_key_exists('newPassword', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('newPassword', 'string');
        }
        $newPassword = $body['newPassword'];

        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        if (!array_key_exists($uniqueIdentifier, $data)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate($uniqueIdentifier, 'string');
        }
        $uniqueIdentifierValue = $data[$uniqueIdentifier];

        if ($currentPassword === $newPassword) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('newPassword', 'password which is not identical to the old password', '<redacted>');
        }

        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            sprintf(
                'MATCH (u:User {%s: $uniqueIdentifierValue}) RETURN u.id AS id, u._passwordHash AS passwordHash LIMIT 1;',
                $uniqueIdentifier
            ),
            [
                'uniqueIdentifierValue' => $uniqueIdentifierValue,
            ]
        ));

        if (0 === count($res)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $userUuid = $res->first()->get('id');
        if (null === $userUuid) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('User UUID should not be null');
        }
        $userUuid = UuidV4::fromString($userUuid);

        $anonymousUserUuid = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserUuid)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('anonymousUserUUID must be set to a valid UUID');
        }
        $anonymousUserUuid = UuidV4::fromString($anonymousUserUuid);
        if ($userUuid->equals($anonymousUserUuid)) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $userElement = $this->elementManager->getElement($userUuid);
        if (null === $userElement) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('User element could not be returned, even though it should.');
        }

        if (!$userElement->hasProperty('_passwordHash')) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('User should have _passwordHash property.');
        }
        $currentPasswordHash = $userElement->getProperty('_passwordHash');
        if (null === $currentPasswordHash) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Password hash should not be null.');
        }

        if (true !== $this->userPasswordHasher->verifyPassword($currentPassword, $currentPasswordHash)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $newPasswordHash = $this->userPasswordHasher->hashPassword($newPassword);

        $userElement->addProperty('_passwordHash', $newPasswordHash);
        $this->elementManager->merge($userElement);
        $this->elementManager->flush();

        return new NoContentResponse();
    }
}
