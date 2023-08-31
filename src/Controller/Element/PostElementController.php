<?php

namespace App\Controller\Element;

use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Helper\Regex;
use App\Response\CreatedResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\AccessType;
use App\Type\ElementType;
use App\Type\NodeElement;
use App\Type\RelationElement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PostElementController extends AbstractController
{
    public function __construct(
        private AuthProvider $authProvider,
        private AccessChecker $accessChecker,
        private ElementManager $elementManager,
        private UrlGeneratorInterface $router,
        private Client400ReservedIdentifierExceptionFactory $client400ReservedIdentifierExceptionFactory,
        private Client400MissingPropertyExceptionFactory $client400MissingPropertyExceptionFactory,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'post-element',
        requirements: [
            'uuid' => Regex::UUID_V4_CONTROLLER,
        ],
        methods: ['POST']
    )]
    public function postElement(string $uuid, Request $request): Response
    {
        $elementUuid = UuidV4::fromString($uuid);
        $userUuid = $this->authProvider->getUserUuid();

        if (!$userUuid) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $type = $this->accessChecker->getElementType($elementUuid);
        if (ElementType::RELATION === $type) {
            // relations can not own nodes
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::CREATE)) {
            throw $this->client404NotFoundExceptionFactory->createFromTemplate();
        }

        $body = \Safe\json_decode($request->getContent(), true);

        $newNodeUuid = UuidV4::uuid4();
        if (array_key_exists('id', $body)) {
            $newNodeUuid = UuidV4::fromString($body['id']);
            $uuidConflict = null !== $this->accessChecker->getElementType($newNodeUuid);
            if ($uuidConflict) {
                throw $this->client400ReservedIdentifierExceptionFactory->createFromTemplate($newNodeUuid->toString());
            }
        }

        if (!array_key_exists('type', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('type', 'a valid type');
        }
        $type = $body['type'];

        if (!array_key_exists('data', $body)) {
            throw $this->client400MissingPropertyExceptionFactory->createFromTemplate('data', 'an object');
        }
        $data = $body['data'];

        $newNode = (new NodeElement())
            ->setIdentifier($newNodeUuid)
            ->setLabel($type)
            ->addProperties($data);

        $newNodeOwnsRelation = (new RelationElement())
            ->setIdentifier(UuidV4::uuid4())
            ->setType('OWNS')
            ->setStart($elementUuid)
            ->setEnd($newNodeUuid);

        $newNodeCreatedRelation = (new RelationElement())
            ->setIdentifier(UuidV4::uuid4())
            ->setType('CREATED')
            ->setStart($this->authProvider->getUserUuid())
            ->setEnd($newNodeUuid);

        $this->elementManager
            ->create($newNode)
            ->create($newNodeOwnsRelation)
            ->create($newNodeCreatedRelation)
            ->flush();

        return new CreatedResponse(
            $this->router->generate(
                'get-element',
                [
                    'uuid' => $newNodeUuid,
                ]
            )
        );
    }
}
