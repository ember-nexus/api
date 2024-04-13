<?php

namespace App\Controller\User;

use App\Exception\Client400BadContentException;
use App\Exception\Client400MissingPropertyException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\UserPasswordHasher;
use App\Service\ElementManager;
use App\Type\NodeElement;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
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
        if (!$this->emberNexusConfiguration->isRegisterEnabled()) {
            throw $this->client403ForbiddenExceptionFactory->createFromTemplate();
        }

        $body = \Safe\json_decode($request->getContent(), true);
        $data = $this->getDataFromBody($body);

        $this->validateTypeFromBody($body);
        $userId = UuidV4::uuid4();
        $password = $this->getPasswordFromBody($body);
        $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromBodyAndData($body, $data);
        $this->checkForDuplicateUniqueUserIdentifier($uniqueUserIdentifier);

        $userNode = $this->createUserNode($userId, $data, $uniqueUserIdentifier, $password);

        $this->elementManager->create($userNode);
        $this->elementManager->flush();

        return $this->createCreatedResponse($userId);
    }

    /**
     * @param array<string, mixed> $body
     *
     * @return array<string, mixed>
     */
    private function getDataFromBody(array $body): array
    {
        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    private function getPasswordFromBody(array $body): string
    {
        if (!array_key_exists('password', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('password', 'string');
        }
        $password = $body['password'];
        if (!is_string($password)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('password', 'string', gettype($password));
        }

        return $body['password'];
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    private function validateTypeFromBody(array $body): void
    {
        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'string');
        }
        $type = $body['type'];
        if (!is_string($type)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'string', gettype($type));
        }
        if ('User' !== $type) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('type', 'User', $body['type']);
        }
    }

    /**
     * @deprecated will be removed in version 0.2.0
     * @see GitHub issue #280
     *
     * @param array<string, mixed> $data
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    private function getUniqueUserIdentifierFromDataOld(array $data): string
    {
        $uniqueIdentifier = $this->emberNexusConfiguration->getRegisterUniqueIdentifier();
        if (!array_key_exists($uniqueIdentifier, $data)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate(sprintf('data.%s', $uniqueIdentifier), 'string');
        }
        $uniqueUserIdentifier = $data[$uniqueIdentifier];
        if (!is_string($uniqueUserIdentifier)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate(sprintf('data.%s', $uniqueIdentifier), 'string', gettype($uniqueUserIdentifier));
        }

        return $uniqueUserIdentifier;
    }

    /**
     * @param array<string, mixed> $body
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    private function getUniqueUserIdentifierFromBodyNew(array $body): string
    {
        if (!array_key_exists('uniqueUserIdentifier', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string');
        }
        $uniqueUserIdentifier = $body['uniqueUserIdentifier'];
        if (!is_string($uniqueUserIdentifier)) {
            throw $this->client400BadContentExceptionFactory->createFromTemplate('uniqueUserIdentifier', 'string', gettype($uniqueUserIdentifier));
        }

        return $uniqueUserIdentifier;
    }

    /**
     * Function's content will be replaced by the content of function self::getUniqueUserIdentifierFromBodyNew with the
     * release of version 0.2.0.
     *
     * @param array<string, mixed> $body
     * @param array<string, mixed> $data
     *
     * @throws Client400BadContentException
     * @throws Client400MissingPropertyException
     */
    private function getUniqueUserIdentifierFromBodyAndData(array $body, array $data): string
    {
        $uniqueUserIdentifier = null;
        if (!$this->emberNexusConfiguration->isFeatureFlag280OldUniqueUserIdentifierDisabled()) {
            try {
                /**
                 * @psalm-suppress DeprecatedMethod
                 */
                $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromDataOld($data);
            } catch (Exception $e) {
            }
        }
        if (null === $uniqueUserIdentifier) {
            $uniqueUserIdentifier = $this->getUniqueUserIdentifierFromBodyNew($body);
        }

        return $uniqueUserIdentifier;
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
            ->setIdentifier($userId)
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
