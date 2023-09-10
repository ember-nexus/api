<?php

namespace App\Controller\User;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PostTokenController extends AbstractController
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private UserPasswordHasher $userPasswordHasher
    ) {
    }

    #[Route(
        '/token',
        name: 'post-token',
        methods: ['POST']
    )]
    public function postToken(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);
        /**
         * @var array<string, mixed> $body
         */
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        if (!array_key_exists($uniqueIdentifier, $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate($uniqueIdentifier, 'string');
        }
        $uniqueIdentifierValue = $body[$uniqueIdentifier];

        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            sprintf(
                'MATCH (u:User {%s: $identifier}) RETURN u.id AS id',
                $uniqueIdentifier,
            ),
            [
                'identifier' => $uniqueIdentifierValue,
            ]
        ));
        if (0 === count($res)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $userUuid = Uuid::fromString($res->first()->get('id'));

        $userElement = $this->elementManager->getElement($userUuid);
        if (null === $userElement) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to load user element from id which was just returned.');
        }

        if (!array_key_exists('password', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('password', 'string');
        }
        $password = $body['password'];

        if (true !== $this->userPasswordHasher->verifyPassword($password, $userElement->getProperty('_passwordHash'))) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $lifetimeInSeconds = null;
        if (array_key_exists('lifetimeInSeconds', $body)) {
            $lifetimeInSeconds = (int) $body['lifetimeInSeconds'];
        }

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'string');
        }
        if ('Token' !== $body['type']) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'Token', $body['type']);
        }

        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }

        $token = $this->tokenGenerator->createNewToken($userUuid, $data, $lifetimeInSeconds);

        return new JsonResponse([
            'type' => '_TokenResponse',
            'token' => $token,
        ]);
    }
}
