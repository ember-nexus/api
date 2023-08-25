<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client400IncompleteMutualDependencyExceptionFactory;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Response\CreatedResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\AccessType;
use App\Type\NodeElement;
use App\Type\RelationElement;
use DateTime;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostIndexController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UrlGeneratorInterface $router,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client400IncompleteMutualDependencyExceptionFactory $client400IncompleteMutualDependencyExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/',
        name: 'postIndex',
        methods: ['POST']
    )]
    public function postIndex(Request $request): Response
    {
        $userId = $this->authProvider->getUserUuid();
        if (!$userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $body = \Safe\json_decode($request->getContent(), true);

        if (array_key_exists('id', $body)) {
            $elementId = UuidV4::fromString($body['id']);
        } else {
            $elementId = UuidV4::uuid4();
        }

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'a valid type');
        }
        $type = $body['type'];

        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    if (strlen($value) >= 22 && strlen($value) <= 26) {
                        $possibleDate = DateTime::createFromFormat(DateTime::ATOM, $value);
                        if (false !== $possibleDate) {
                            $data[$key] = $possibleDate;
                        }
                    }
                }
            }
        }

        $startId = null;
        if (array_key_exists('start', $body)) {
            $startId = UuidV4::fromString($body['start']);
        }

        $endId = null;
        if (array_key_exists('end', $body)) {
            $endId = UuidV4::fromString($body['end']);
        }

        if (null === $startId && null === $endId) {
            return $this->createNode($type, $elementId, $data);
        }

        if (null !== $startId && null !== $endId) {
            return $this->createRelation($type, $elementId, $startId, $endId, $data);
        }

        $setProperties = [];
        $missingProperties = [];
        if (null !== $startId) {
            $setProperties[] = 'start';
        } else {
            $missingProperties[] = 'start';
        }
        if (null !== $endId) {
            $setProperties[] = 'end';
        } else {
            $missingProperties[] = 'end';
        }
        throw $this->client400IncompleteMutualDependencyExceptionFactory->createFromTemplate(['start', 'end'], $setProperties, $missingProperties);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createNode(string $type, UuidInterface $nodeId, array $data): Response
    {
        $userId = $this->authProvider->getUserUuid();
        if (!$userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $newNode = (new NodeElement())
            ->setIdentifier($nodeId)
            ->setLabel($type)
            ->addProperties($data);

        $newNodeOwnsRelation = (new RelationElement())
            ->setIdentifier(UuidV4::uuid4())
            ->setType('OWNS')
            ->setStart($userId)
            ->setEnd($nodeId);

        $newNodeCreatedRelation = (new RelationElement())
            ->setIdentifier(UuidV4::uuid4())
            ->setType('CREATED')
            ->setStart($userId)
            ->setEnd($nodeId);

        $this->elementManager
            ->create($newNode)
            ->create($newNodeOwnsRelation)
            ->create($newNodeCreatedRelation)
            ->flush();

        return new CreatedResponse(
            $this->router->generate(
                'getElement',
                [
                    'uuid' => $nodeId,
                ]
            )
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createRelation(string $type, UuidInterface $relationId, UuidInterface $startId, UuidInterface $endId, array $data): Response
    {
        $userId = $this->authProvider->getUserUuid();
        if (!$userId) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        if (!$this->accessChecker->hasAccessToElement($userId, $startId, AccessType::CREATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }
        if (!$this->accessChecker->hasAccessToElement($userId, $endId, AccessType::READ)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $newRelation = (new RelationElement())
            ->setIdentifier($relationId)
            ->setType($type)
            ->setStart($startId)
            ->setEnd($endId)
            ->addProperties($data);

        $this->elementManager
            ->create($newRelation)
            ->flush();

        return new CreatedResponse(
            $this->router->generate(
                'getElement',
                [
                    'uuid' => $relationId,
                ]
            )
        );
    }
}
