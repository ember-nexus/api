<?php

namespace App\Controller\User;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Exception\Client401UnauthorizedException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Response\JsonResponse;
use App\Security\TokenGenerator;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Service\RequestUtilService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class PostTokenController extends AbstractController
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private UserPasswordHasher $userPasswordHasher,
        private RequestUtilService $requestUtilService
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
        $data = $this->requestUtilService->getDataFromBody($body);

        $this->requestUtilService->validateTypeFromBody('Token', $body);
        $uniqueUserIdentifier = $this->requestUtilService->getUniqueUserIdentifierFromBodyAndData($body, $data);

        $userElement = $this->findUserByUniqueUserIdentifier($uniqueUserIdentifier);
        $userId = $userElement->getIdentifier();
        if (null === $userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $this->verifyPasswordMatches($body, $userElement);

        $lifetimeInSeconds = $this->getLifetimeInSecondsFromBody($body);
        $data = $this->requestUtilService->getDataFromBody($body);
        $token = $this->tokenGenerator->createNewToken($userId, $data, $lifetimeInSeconds);

        return $this->createTokenResponse($token);
    }

    private function createTokenResponse(string $token): JsonResponse
    {
        return new JsonResponse([
            'type' => '_TokenResponse',
            'token' => $token,
        ]);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     * @throws Client401UnauthorizedException
     */
    private function verifyPasswordMatches(array $body, NodeElementInterface $userElement): void
    {
        $password = $this->requestUtilService->getPasswordFromBody($body);
        if (!$userElement->hasProperty('_passwordHash')) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $hashedPassword = $userElement->getProperty('_passwordHash');
        if (true !== $this->userPasswordHasher->verifyPassword($password, $hashedPassword)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     */
    private function getLifetimeInSecondsFromBody(array $body): ?int
    {
        if (!array_key_exists('lifetimeInSeconds', $body)) {
            return null;
        }
        $lifetimeInSeconds = $body['lifetimeInSeconds'];
        if (!is_int($lifetimeInSeconds)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('lifetimeInSeconds', 'int', gettype($lifetimeInSeconds));
        }

        return $lifetimeInSeconds;
    }

    private function findUserByUniqueUserIdentifier(string $uniqueUserIdentifier): NodeElementInterface
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
