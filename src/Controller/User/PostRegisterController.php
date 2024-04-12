<?php

namespace App\Controller\User;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Type\NodeElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class PostRegisterController extends AbstractController
{
    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private UrlGeneratorInterface $router,
        private UserPasswordHasher $userPasswordHasher,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
    ) {
    }

    #[Route(
        '/register',
        name: 'post-register',
        methods: ['POST']
    )]
    public function postRegister(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);
        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }
        /**
         * @var array<string, mixed> $data
         */
        if (!$this->emberNexusConfiguration->isRegisterEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $userId = UuidV4::uuid4();

        if (!array_key_exists('password', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('password', 'string');
        }
        $password = $body['password'];

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'string');
        }
        if ('User' !== $body['type']) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'User', $body['type']);
        }

        // @todo 0.2.0: remove old logic, see also #280
        $uniqueUserIdentifier = null;
        if (!$this->emberNexusConfiguration->isFeatureFlag280OldUniqueUserIdentifierDisabled()) {
            $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
            if (array_key_exists($uniqueIdentifier, $data)) {
                $uniqueUserIdentifier = $data[$uniqueIdentifier];
            }
        }
        if (null === $uniqueUserIdentifier) {
            if (!array_key_exists('uniqueUserIdentifier', $body)) {
                throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string');
            }
            $uniqueUserIdentifier = $body['uniqueUserIdentifier'];
        }

        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            sprintf(
                'MATCH (u:User {%s: $uniqueUserIdentifier}) RETURN count(u) AS count',
                $uniqueIdentifier
            ),
            [
                'uniqueUserIdentifier' => $uniqueUserIdentifier,
            ]
        ));
        if ($res->first()->get('count') > 0) {
            throw $this->client400ReservedIdentifierExceptionFactory->createFromTemplate($uniqueUserIdentifier);
        }

        $userNode = (new NodeElement())
            ->setIdentifier($userId)
            ->setLabel('User')
            ->addProperties([
                ...$data,
                $uniqueIdentifier => $uniqueUserIdentifier,
                '_passwordHash' => $this->userPasswordHasher->hashPassword($password),
            ]);

        $this->elementManager->create($userNode);
        $this->elementManager->flush();

        return new CreatedResponse(
            $this->router->generate(
                'get-element',
                [
                    'uuid' => $userId,
                ]
            )
        );
    }
}
