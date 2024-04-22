<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use App\Type\NodeElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
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
        private Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory,
        private Client403ForbiddenExceptionFactory $client403ForbiddenExceptionFactory,
        private RequestUtilService $requestUtilService
    ) {
    }

    #[Route(
        '/register',
        name: 'post-register',
        methods: ['POST']
    )]
    public function postRegister(Request $request): Response
    {
        if (!$this->emberNexusConfiguration->isRegisterEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $body = \Safe\json_decode($request->getContent(), true);
        $data = $this->requestUtilService->getDataFromBody($body);

        $this->requestUtilService->validateTypeFromBody('User', $body);
        $userId = UuidV4::uuid4();
        $password = $this->requestUtilService->getStringFromBody('password', $body);
        $uniqueUserIdentifier = $this->requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $data);
        $this->checkForDuplicateUniqueUserIdentifier($uniqueUserIdentifier);

        $userNode = $this->createUserNode($userId, $data, $uniqueUserIdentifier, $password);

        $this->elementManager->create($userNode);
        $this->elementManager->flush();

        return $this->createCreatedResponse($userId);
    }

    /**
     * @throws \App\Exception\Client400ReservedIdentifierException
     */
    private function checkForDuplicateUniqueUserIdentifier(string $uniqueUserIdentifier): void
    {
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
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
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createUserNode(UuidInterface $userId, array $data, string $uniqueUserIdentifier, string $password): NodeElement
    {
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        $userNode = (new NodeElement())
            ->setId($userId)
            ->setLabel('User')
            ->addProperties([
                ...$data,
                $uniqueIdentifier => $uniqueUserIdentifier,
                '_passwordHash' => $this->userPasswordHasher->hashPassword($password),
            ]);

        return $userNode;
    }

    private function createCreatedResponse(UuidInterface $userId): CreatedResponse
    {
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
