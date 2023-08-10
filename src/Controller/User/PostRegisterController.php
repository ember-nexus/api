<?php

namespace App\Controller\User;

use App\Exception\ClientBadRequestException;
use App\Exception\ClientForbiddenException;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Type\NodeElement;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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
        private ParameterBagInterface $bag,
        private UserPasswordHasher $userPasswordHasher
    ) {
    }

    #[Route(
        '/register',
        name: 'postRegister',
        methods: ['POST']
    )]
    public function postRegister(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        $registerConfig = $this->bag->get('register');
        if (null === $registerConfig) {
            throw new \Exception("Unable to get unique identifier from config; key 'register' must exist.");
        }
        if (!is_array($registerConfig)) {
            throw new \Exception("Configuration key 'register' must be an array.");
        }
        if (($registerConfig['enabled'] ?? false) === false) {
            throw new ClientForbiddenException();
        }

        $userId = UuidV4::uuid4();

        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }
        /**
         * @var array<string, mixed> $data
         */
        if (!array_key_exists('password', $body)) {
            throw new ClientBadRequestException(detail: 'Property password must be set.');
        }
        $password = $body['password'];

        $uniqueIdentifier = $registerConfig['uniqueIdentifier'];
        if (null === $uniqueIdentifier) {
            throw new \Exception("Unable to get unique identifier from config; key 'register.uniqueIdentifier' must exist.");
        }
        if (!array_key_exists($uniqueIdentifier, $data)) {
            throw new ClientBadRequestException(detail: sprintf("Property '%s' must be set.", $uniqueIdentifier));
        }
        $uniqueIdentifierValue = $data[$uniqueIdentifier];

        $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            sprintf(
                'MATCH (u:User {%s: $identifier}) RETURN count(u) AS count',
                $uniqueIdentifier
            ),
            [
                'identifier' => $uniqueIdentifierValue,
            ]
        ));
        if ($res->first()->get('count') > 0) {
            throw new ClientBadRequestException(sprintf("Value '%s' for property '%s' is not available.", $uniqueIdentifierValue, $uniqueIdentifier));
        }

        $userNode = (new NodeElement())
            ->setIdentifier($userId)
            ->setLabel('User')
            ->addProperties([
                ...$data,
                '_passwordHash' => $this->userPasswordHasher->hashPassword($password),
            ]);

        $this->elementManager->create($userNode);
        $this->elementManager->flush();

        return new CreatedResponse(
            $this->router->generate(
                'getElement',
                [
                    'uuid' => $userId,
                ]
            )
        );
    }
}
