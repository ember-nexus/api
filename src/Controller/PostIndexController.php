<?php

namespace App\Controller;

use App\Exception\ClientBadRequestException;
use App\Exception\ClientNotFoundException;
use App\Exception\ClientUnauthorizedException;
use App\Response\CreatedResponse;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\ElementManager;
use App\Type\AccessType;
use App\Type\NodeElement;
use App\Type\RelationElement;
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
        private UrlGeneratorInterface $router
    ) {
    }

    #[Route(
        '/',
        name: 'postIndex',
        methods: ['POST']
    )]
    public function postIndex(Request $request): Response
    {
        $body = \Safe\json_decode($request->getContent(), true);

        if (array_key_exists('id', $body)) {
            $elementId = UuidV4::fromString($body['id']);
        } else {
            $elementId = UuidV4::uuid4();
        }

        if (!array_key_exists('type', $body)) {
            throw new ClientBadRequestException(detail: 'Type must be set.');
        }
        $type = $body['type'];

        $data = [];
        if (array_key_exists('data', $body)) {
            $data = $body['data'];
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

        throw new ClientBadRequestException(detail: "When creating a relation, both properties 'start' as well as 'end' must be set.");
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createNode(string $type, UuidInterface $nodeId, array $data): Response
    {
        $userId = $this->authProvider->getUserUuid();

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
            throw new ClientUnauthorizedException();
        }

        if (!$this->accessChecker->hasAccessToElement($userId, $startId, AccessType::CREATE)) {
            throw new ClientNotFoundException();
        }
        if (!$this->accessChecker->hasAccessToElement($userId, $endId, AccessType::READ)) {
            throw new ClientNotFoundException();
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
