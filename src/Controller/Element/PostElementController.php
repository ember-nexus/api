<?php

namespace App\Controller\Element;

use App\Exception\ClientBadIdException;
use App\Exception\ClientBadRequestException;
use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
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
        private UrlGeneratorInterface $router
    ) {
    }

    #[Route(
        '/{uuid}',
        name: 'postElement',
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
            throw new ClientUnauthorizedException();
        }

        $type = $this->accessChecker->getElementType($elementUuid);
        if (ElementType::RELATION === $type) {
            // relations can not own nodes
            throw new ClientNotFoundException();
        }

        if (!$this->accessChecker->hasAccessToElement($userUuid, $elementUuid, AccessType::CREATE)) {
            throw new ClientNotFoundException();
        }

        $body = \Safe\json_decode($request->getContent(), true);

        $newNodeUuid = UuidV4::uuid4();
        if (array_key_exists('id', $body)) {
            $newNodeUuid = UuidV4::fromString($body['id']);
            $elementTypeOfNewNodeUuid = $this->accessChecker->getElementType($newNodeUuid);
            if (null !== $elementTypeOfNewNodeUuid) {
                throw new ClientBadIdException();
            }
        }

        if (!array_key_exists('type', $body)) {
            throw new ClientBadRequestException(detail: 'Required property "type" is not set.');
        }
        $type = $body['type'];

        if (!array_key_exists('data', $body)) {
            throw new ClientBadRequestException(detail: 'Required property "data" is not set.');
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
                'getElement',
                [
                    'uuid' => $newNodeUuid,
                ]
            )
        );
    }
}
